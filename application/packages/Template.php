<?php

/**
*
*/
class Template extends \Schema {

    /**
    *
    */
    const SCHEMA_FIELD_IS_READONLY = false;
    const SCHEMA_FIELD_MISMATCH_SET_NULL = false;
    const SCHEMA_FIELDS = [
        'dirname' => self::SCHEMA_FIELD_IS_DIRECTORY,
        'filename' => self::SCHEMA_FIELD_IS_FILE,
        'file_contents' => self::SCHEMA_FIELD_IS_CONTENT,
        'args' => self::SCHEMA_FIELD_IS_LIST,
    ];

    /**
    *
    */
    public function __construct(string $filename, array $args = []) {
        parent::__construct([
            'dirname' => dirname($filename) . '/',
            'filename' => $filename,
            'file_contents' => $filename,
            'args' => $args,
        ]);

        $this->__values['file_contents'] = $this->init($this->file_contents);
    }

    /**
    *
    */
    public function __toString() {
        return (string) $this->execute();
    }

    /**
    *
    */
    public function arg(string $name, $mixed_value = null) {
        if (is_null($mixed_value))
            return eval('return $this->args["' . implode('"]["', explode('.', $name)) . '"] ?? null;');
        
        $args = $this->args;
        eval('$args["' . implode('"]["', explode('.', $name)) . '"] = $mixed_value;');
        $this->args = $args;
    }

    /**
    *
    */
    public function init(string $file_contents, ?string $source_filename = null, int $extends_limit = 25): string {
        $patterns = [
            '/{%\s+(include|include_once|require|require_once)\s*\(?(.+)\)?\s+%}/isU' => function(string $file_contents, array $matches): string {
                $ENVIRONMENT_PATH = $this->dirname;

                foreach ($matches as $i => $match)
                    $file_contents = str_replace($match[0], eval(sprintf('%s(%s);', $match[1], $match[2])), $file_contents);

                return $file_contents;
            },
            '/{%\s+extends\s*"(.+)"\s+%}/isU' => function(string $file_contents, array $matches) use ($source_filename, $extends_limit): string {
                $ENVIRONMENT_PATH = $this->dirname;

                foreach ($matches as $i => $match) {
                    if ($match[1] == $source_filename)
                        $file_contents = str_replace($match[0], sprintf('<!-- extends "%s": Attempt to extend the same file_contents, aborting! -->', $match[1]),  $file_contents);
                    else if ($extends_limit <= 0)
                        $file_contents = str_replace($match[0], sprintf('<!-- extends "%s": The maximum limit to extend the file_contents reached, aborting! -->', $match[1]),  $file_contents);
                    else if (($content = @file_get_contents($match[1])) !== false)
                        $file_contents = str_replace($match[0], $this->init($content, $match[1], -- $extends_limit),  $file_contents);
                    else
                        $file_contents = str_replace($match[0], sprintf('<!-- extends "%s": Failed to open stream, no such file found. -->', $match[1]), $file_contents);
                }

                return $file_contents;
            },
        ];
        
        foreach($patterns as $pattern => $callback)
            if (preg_match_all($pattern, $file_contents, $matches, PREG_SET_ORDER))
                $file_contents = $callback($file_contents, $matches);
            
        return $file_contents;
    }

    /**
    *
    */
    public function translate(?string $file_contents = null): string {
        $file_contents = $file_contents ?? $this->file_contents;
        $interpolation = function(string $expression): string {
            $variables = [];
            $double_quote_opened = false;
            $keywords = [
                '&&', '||', ',', ';', '(', ')', '!', '?', ':', '===', '==', '=',
                '!==', '!=', '>=', '<=', '<>', '>', '<','%', '*', '^', '/', '+', '-',
                'return', 'break', 'continue',
            ];
            $pattern = '/\s*(' . str_replace('`', '|', preg_quote(implode('`', $keywords), '/')) . ')\s*/is';
        
            foreach (preg_split($pattern, $expression, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY) as $i => $match) {
                if ($double_quote_opened || $double_quote_opened = preg_match('/^"/isU', $match))
                    $variables[$i] = $match;
        
                if (preg_match('/"$/isU', $match))
                    $double_quote_opened = false;
                    
                if (!($match = trim($match))
                    || (in_array($match, $keywords))
                    || is_numeric($match)
                    || function_exists($match)
                    || defined($match)
                    || preg_match('/^\$/isU', $match))
                    $variables[$i] = $match;
        
                if (!isset($variables[$i])) {
                    if (($list = explode('.', $match)) && count($list) > 1) {
                        $variable = array_shift($list);
                        $variables[$i] = sprintf('$%s["%s"]', $variable, implode('"]["', $list));
                    } else
                        $variables[$i] = sprintf('$%s', $match);
                }
            }
        
            return implode(null, $variables);
        };

        $patterns = [
            '/\{%\s+(end|endfor|endif|close)\s+%\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $file_contents = str_replace($match[0], '<?php } ?>', $file_contents);
            
                return $file_contents;
            },
            '/\{%\s+(each|for|foreach)\s+([a-z0-9_]+)\s*(,\s*([a-z0-9_]*))?\s+in\s+(.+)\s+%\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match) {
                    if ($match[2] && $match[3])
                        $php_code = sprintf('<?php foreach(%s as $%s => $%s) { ?>', $interpolation($match[5]), $match[2], $match[4]);
                    else
                        $php_code = sprintf('<?php foreach(%s as $%s) { ?>', $interpolation($match[5]), $match[2]);
            
                    $file_contents = str_replace($match[0], $php_code, $file_contents);
                }
            
                return $file_contents;
            },
            '/\{%\s+(if)\s+(.+)\s+%\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach($matches as $i => $match)
                    $file_contents = str_replace($match[0], sprintf('<?php if (%s) { ?>', $interpolation($match[2])), $file_contents);

                return $file_contents;
            },
            '/\{%\s+(else\s*if|elif)\s+(.+)\s+%\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach($matches as $i => $match)
                    $file_contents = str_replace($match[0], sprintf('<?php } else if (%s) { ?>', $interpolation($match[2])), $file_contents);

                return $file_contents;
            },
            '/\{%\s+(else)\s+%\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $file_contents = str_replace($match[0], '<?php } else { ?>', $file_contents);
            
                return $file_contents;
            },
            '/\{\{\s+(.+)\s+\}\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $file_contents = str_replace($match[0], sprintf('<?= %s ?>', $interpolation($match[1])), $file_contents);
        
                return $file_contents;
            },
            '/\{\{!\s+([a-z0-9_\.]+)\s+\}\}/isU' => function(string $file_contents, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $file_contents = str_replace($match[0], $interpolation($match[1]), $file_contents);
        
                return $file_contents;
            }
        ];

        foreach($patterns as $pattern => $callback)
            if (preg_match_all($pattern, $file_contents, $matches, PREG_SET_ORDER))
                $file_contents = $callback($file_contents, $matches);

        return (string) $file_contents;
    }

    /**
    *
    */
    public function execute(bool $translate = true, bool $evalutate = true): string {
        ob_start();
            $file_contents = $translate ? $this->translate() : $this->file_contents;

            if ($translate && $evalutate) {
                extract($this->args);
                echo eval('?>' . $file_contents);
            } else if ($evalutate)
                echo eval('?>' . $file_contents);
            else
                echo $file_contents;

        return ob_get_clean();
    }
}
?>