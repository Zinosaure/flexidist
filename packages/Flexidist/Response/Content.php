<?php

/**
*
*/
namespace Flexidist\Response;

/**
*
*/
class Content {

    /**
    *
    */
    use \traits\dotnotation;
    
    protected $template = null;
    public static $branches = [];
    
    /**
    *
    */
    public function __construct(?string $template = null, array $variables = [], int $max_extends_limit = 10) {
        if (!is_null($template) && preg_match('/\.phtml$/isU', $template) && is_file(TEMPLATES_PATH . $template))
            $template = file_get_contents(TEMPLATES_PATH . $template);

        $this->template = $this->build($template ?: implode("\n", [
            '<!DOCTYPE html>',
            '<html {{ function.html_attributes(html.attributes) }}>',
            "\t" . '<head {{ function.html_attributes(html.head.attributes) }}>',
            '<branch name="head_content" template="extends/head_content.phtml" />',
            "\t" . '</head>',
            "\t" . '<body {{ function.html_attributes(html.body.attributes) }}>',
            '<branch name="html_content" template="extends/html_content.phtml" />',
            "\t" . '</body>',
            '</html>',
        ]), null, $max_extends_limit);
        $this->dn_init(array_replace_recursive([
            'function' => [
                'html_attributes' => function(array $attributes): string {
                    $stringified_attributes = [];
                    
                    foreach ($attributes as $name => $value)
                        if (!empty($name) && is_string($name) && (is_string($value) || is_null($value))  && $value !== false)
                            $stringified_attributes[] = sprintf(' %s="%s"', $name, htmlspecialchars($value));
                        
                    return trim(implode(null, $stringified_attributes));
                },
            ],
            'html' => [
                'attributes' => [
                    'lang' => 'en-US',
                    'itemscope' => null,
                    'itemtype' => 'http://schema.org/WebPage',
                    'xmlns' => 'http://www.w3.org/1999/xhtml',
                ],
                'head' => [
                    'attributes' => [
                        'prefix' => 'og:http://ogp.me/ns# fb:http://ogp.me/ns/fb# website:http://ogp.me/ns/website#',
                    ],
                    'base_href' => SERVER_NAME . DOCUMENT_ROOT,
                    'title' => null,
                    'description' => null,
                    'content' => null,
                ],
                'body' => [
                    'attributes' => [],
                    'content' => null,
                ],
            ],
        ], $variables));
    }

    /**
    *
    */
    public static function checkout(string $name, string $template = null): self {
        if (isset(self::$branches[$name]))
            return self::$branches[$name];

        return self::$branches[$name] = new self($template);
    }

    /**
    *
    */
    public static function transform(string $expression): string {
        $variables = [];
        $double_quote_opened = false;
        $keywords = [
            '&&' => ' && ',
            '||' => ' || ',
            '&' => ' && ',
            '|' => ' || ',
            ',' => ', ',
            '(' => '(',
            ')' => ')',
            '!' => '!',
            '::' => '::',
            '?' => ' ?',
            ':' => ': ',
            '==' => ' == ',
            '===' => ' === ',
            '!=' => ' != ',
            '!==' => ' !== ',
            '>=' => ' >= ',
            '<=' => ' <= ',
            '<>' => ' <> ',
            '>' => '>',
            '<' => '<',
            '=' => ' = ',
            '%' => ' % ',
            '*' => ' * ',
            '/' => ' / ',
            '+' => ' + ',
            '-' => ' - ',
        ];
        $keywords_keys = array_keys($keywords);
    
        foreach (preg_split('/\s*(&&|\|\||&|\||,|\)|\(|={1,3}|!={1,2}|>=|<=|<>|>|<|!|\?|:{1,2}|%|\*|\/|\-|\+)\s*/is', $expression, -1, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY) as $i => $match) {
            if ($double_quote_opened || $double_quote_opened = preg_match('/^"/isU', $match))
                $variables[$i] = $match;
    
            if (preg_match('/"$/isU', $match))
                $double_quote_opened = false;
                
            if (!($match = trim($match))
                || (in_array($match, $keywords_keys) && $match = $keywords[$match])
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
    }

    /**
    *
    */
    public function rebase(string $template, int $max_extends_limit = 10): self {
        if ($template && preg_match('/\.phtml$/isU', $template) && is_file(TEMPLATES_PATH . $template))
            $template = file_get_contents(TEMPLATES_PATH . $template);

        $this->template = $this->build($template, null, $max_extends_limit);

        return $this;
    }
    
    /**
    *
    */
    public function build(string $template, ?string $source_filename = null, int $max_extends_limit = 10): string {
        $patterns = [
            '/{%\s+(extend|import)\s*\((.+)\)\s+%}/isU' => function(string $template, array $matches) use ($source_filename, $max_extends_limit): string {
                ob_start();
                    foreach ($matches as $i => $match) {
                        if ($match[2] == $source_filename)
                            $template = str_replace($match[0], sprintf('<!-- %s(%s): Attempt to extend/import the same template, aborting! -->', $match[1], $match[2]),  $template);
                        else if ($max_extends_limit <= 0)
                            $template = str_replace($match[0], sprintf('<!-- %s(%s): The maximum limit to extend/import templates reached, aborting! -->', $match[1], $match[2]),  $template);
                        else if (($content = @file_get_contents(eval('return ' . $match[2] . ';'))) !== false)
                            $template = str_replace($match[0], $this->build($content, $match[2], -- $max_extends_limit),  $template);
                        else
                            $template = str_replace($match[0], sprintf('<!-- %s(%s): Failed to open stream, no such file found. -->', $match[1], $match[2]), $template);
                    }

                    echo $template;

                return ob_get_clean();
            },
            '/<branch\s+name="([a-z0-9_]*)"\s+template="(.*)"\s+\/?>/isU' => function(string $template, array $matches) use ($source_filename, $max_extends_limit): string {
                ob_start();
                    foreach ($matches as $i => $match) {
                        if ($source_filename && $match[2] == $source_filename)
                            $template = str_replace($match[0], sprintf('<!-- <branch name="%s" template="%s" message="%s" /> -->', $match[1], $match[2], 'Attempt to branch the same template, aborting!'), $template);
                        else if ($max_extends_limit <= 0)
                            $template = str_replace($match[0], sprintf('<!-- <branch name="%s" template="%s" message="%s" /> -->', $match[1], $match[2], 'The maximum limit to branch templates reached, aborting!'), $template);
                        else if (!($content = @file_get_contents(TEMPLATES_PATH . $match[2])) !== false)
                        	$template = str_replace($match[0], sprintf('<!-- <branch name="%s" template="%s" message="%s" /> -->', $match[1], $match[2], 'Failed to open stream, no such file found.'), $template);
                        else {
                        	$template = str_replace($match[0], sprintf('<checkout name="%s" />', $match[1]), $template);
                            self::$branches[$match[1]] = new self($this->build($content, $match[2], -- $max_extends_limit));
                        }
                    }

                    echo $template;

                return ob_get_clean();
            },
        ];
       
        foreach($patterns as $pattern => $callback)
            if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER))
                $template = $callback($template, $matches);
            
        return $template;
    }

    /**
    *
    */
    public function format(): string {
        $patterns = [
            '/<checkout\s+name="([a-z0-9_]*)"\s+\/>/isU' => function(string $template, array $matches): string {
                ob_start();
                    foreach ($matches as $i => $match)
                        if (isset(self::$branches[$match[1]]))
                            $template = str_replace($match[0], self::checkout($match[1])->format(), $template);
                            
                    echo $template;

                return ob_get_clean();
            },
            '/{%\s+(include|include_once|require|require_once)\s*\((.+)\)\s+%}/isU' => function(string $template, array $matches): string {
                ob_start();
                    foreach ($matches as $i => $match)
                        $template = str_replace($match[0], sprintf('<?php %s(%s) ?>', $match[1], self::transform($match[2])), $template);

                    echo $template;

                return ob_get_clean();
            },
            '/\{%\s+(end|endfor|endif|close)\s+%\}/isU' => function(string $template, array $matches): string {
                foreach ($matches as $i => $match)
                    $template = str_replace($match[0], '<?php } ?>', $template);
            
                return $template;
            },
            '/\{%\s+(for|loop)\s+([a-z0-9_]+)\s*(,\s*([a-z0-9_]*))?\s+in\s+(.+)\s+%\}/isU' => function(string $template, array $matches): string {
                foreach ($matches as $i => $match) {
                    if ($match[2] && $match[3])
                        $php_code = sprintf('<?php foreach(%s as $%s => $%s) { ?>', self::transform($match[5]), $match[2], $match[4]);
                    else
                        $php_code = sprintf('<?php foreach(%s as $%s) { ?>', self::transform($match[5]), $match[2]);
            
                    $template = str_replace($match[0], $php_code, $template);
                }
            
                return $template;
            },
            '/\{%\s+(if)\s+(.+)\s+%\}/isU' => function(string $template, array $matches): string {
                foreach($matches as $i => $match)
                    $template = str_replace($match[0], sprintf('<?php if (%s) { ?>', self::transform($match[2])), $template);

                return $template;
            },
            '/\{%\s+(else\s*if|elif)\s+(.+)\s+%\}/isU' => function(string $template, array $matches): string {
                foreach($matches as $i => $match)
                    $template = str_replace($match[0], sprintf('<?php } else if (%s) { ?>', self::transform($match[2])), $template);

                return $template;
            },
            '/\{%\s+(else)\s+%\}/isU' => function(string $template, array $matches): string {
                foreach ($matches as $i => $match)
                    $template = str_replace($match[0], '<?php } else { ?>', $template);
            
                return $template;
            },
            '/\{\{\s+(.+)\s+\}\}/isU' => function(string $template, array $matches): string {
                foreach ($matches as $i => $match)
                    $template = str_replace($match[0], sprintf('<?= %s ?>', self::transform($match[1])), $template);
        
                return $template;
            },
            '/\{\{!\s+([a-z0-9_\.]+)\s+\}\}/isU' => function(string $template, array $matches): string {
                foreach ($matches as $i => $match)
                    $template = str_replace($match[0], self::transform($match[1]), $template);
        
                return $template;
            }
        ];

        $template = $this->template;

        foreach($patterns as $pattern => $callback)
            if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER))
                $template = $callback($template, $matches);

        return (string) $template;
    }

    /**
    *
    */
    public function prepend(string $template) {
        if (!is_null($template) && preg_match('/\.phtml$/isU', $template) && is_file($template))
            $template = file_get_contents($template);

        $this->template = $template . $this->template;
    }

    /**
    *
    */
    public function append(string $template) {
        if (!is_null($template) && preg_match('/\.phtml$/isU', $template) && is_file($template))
            $template = file_get_contents($template);

        $this->template .= $template;
    }
    
    /**
    *
    */
    public function evaluate(): string {
        ob_start();
            extract($this->dn_get());
            echo eval('?>' . $this->format());

        return ob_get_clean();
    }

    /**
    *
    */
    public function output(bool $evaluate = true) {
    	echo $evaluate ? $this->evaluate() : $this->format();
    }
}
?>