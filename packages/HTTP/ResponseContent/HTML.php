<?php

/**
*
*/
namespace HTTP\ResponseContent;

/**
*
*/
class HTML extends \Schema\Type\JSON {

    /**
    *
    */
    const SCHEMA_VALUE_IS_READONLY = true;
    const SCHEMA_VALIDATE_ATTRIBUTES = [
        'document' => self::SCHEMA_VALIDATE_IS_STRING,
        'args' => self::SCHEMA_VALIDATE_IS_LIST,
    ];

    /**
    *
    */
    public function __construct(?string $document = null, array $vars = []) {
        if (!is_null($document) && preg_match('/\.phtml$/isU', $document) && is_file(TEMPLATES_PATH . $document))
            $document = file_get_contents(TEMPLATES_PATH . $document);

        parent::__construct([
            'document' => $this->init($document ?: implode("\n", [
                '<!DOCTYPE html>',
                '<html {{ fn.html_attributes(html.attributes) }}>',
                "\t" . '<head {{ fn.html_attributes(html.head.attributes) }}>',
                "\t\t" . '{% extends "extends/head_content.phtml" %}',
                "\t" . '</head>',
                "\t" . '<body {{ fn.html_attributes(html.body.attributes) }}>',
                "\t\t" . '{% extends "extends/html_content.phtml" %}',
                "\t" . '</body>',
                '</html>',
            ])),
            'args' => array_replace_recursive($vars, [
                'fn' => [
                    'html_attributes' => function(array $attributes): string {
                        $html_attributes = [];
                        
                        foreach ($attributes as $name => $value)
                            if (!empty($name) && is_string($name) && (is_string($value) || is_null($value))  && $value !== false)
                                $html_attributes[] = sprintf(' %s="%s"', $name, htmlspecialchars($value));
                            
                        return trim(implode(null, $html_attributes));
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
            ]),
        ]);
    }

    /**
    *
    */
    public function __toString(): string {
        return $this->execute();
    }

    /**
    *
    */
    public function init(string $document, ?string $source_filename = null, int $extends_limit = 25): string {
        $patterns = [
            '/{%\s+(include|include_once|require|require_once)\s*\("(.+)"\)\s+%}/isU' => function(string $document, array $matches): string {
                foreach ($matches as $i => $match)
                    $document = str_replace($match[0], eval(sprintf('%s("%s");', $match[1], TEMPLATES_PATH . $match[2])), $document);

                return $document;
            },
            '/{%\s+extends\s*"(.+)"\s+%}/isU' => function(string $document, array $matches) use ($source_filename, $extends_limit): string {
                foreach ($matches as $i => $match) {
                    if ($match[1] == $source_filename)
                        $document = str_replace($match[0], sprintf('<!-- extends "%s": Attempt to extend the same document, aborting! -->', $match[1]),  $document);
                    else if ($extends_limit <= 0)
                        $document = str_replace($match[0], sprintf('<!-- extends "%s": The maximum limit to extend the document reached, aborting! -->', $match[1]),  $document);
                    else if (($content = @file_get_contents(TEMPLATES_PATH . $match[1])) !== false)
                        $document = str_replace($match[0], $this->init($content, $match[1], -- $extends_limit),  $document);
                    else
                        $document = str_replace($match[0], sprintf('<!-- extends "%s": Failed to open stream, no such file found. -->', $match[1]), $document);
                }

                return $document;
            },
        ];
        
        foreach($patterns as $pattern => $callback)
            if (preg_match_all($pattern, $document, $matches, PREG_SET_ORDER))
                $document = $callback($document, $matches);
            
        return $document;
    }

    /**
    *
    */
    public function translate(?string $document = null): string {
        $document = $document ?? $this->document;
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
            '/\{%\s+(end|endfor|endif|close)\s+%\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $document = str_replace($match[0], '<?php } ?>', $document);
            
                return $document;
            },
            '/\{%\s+(each|for|foreach)\s+([a-z0-9_]+)\s*(,\s*([a-z0-9_]*))?\s+in\s+(.+)\s+%\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match) {
                    if ($match[2] && $match[3])
                        $php_code = sprintf('<?php foreach(%s as $%s => $%s) { ?>', $interpolation($match[5]), $match[2], $match[4]);
                    else
                        $php_code = sprintf('<?php foreach(%s as $%s) { ?>', $interpolation($match[5]), $match[2]);
            
                    $document = str_replace($match[0], $php_code, $document);
                }
            
                return $document;
            },
            '/\{%\s+(if)\s+(.+)\s+%\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach($matches as $i => $match)
                    $document = str_replace($match[0], sprintf('<?php if (%s) { ?>', $interpolation($match[2])), $document);

                return $document;
            },
            '/\{%\s+(else\s*if|elif)\s+(.+)\s+%\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach($matches as $i => $match)
                    $document = str_replace($match[0], sprintf('<?php } else if (%s) { ?>', $interpolation($match[2])), $document);

                return $document;
            },
            '/\{%\s+(else)\s+%\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $document = str_replace($match[0], '<?php } else { ?>', $document);
            
                return $document;
            },
            '/\{\{\s+(.+)\s+\}\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $document = str_replace($match[0], sprintf('<?= %s ?>', $interpolation($match[1])), $document);
        
                return $document;
            },
            '/\{\{!\s+([a-z0-9_\.]+)\s+\}\}/isU' => function(string $document, array $matches) use ($interpolation): string {
                foreach ($matches as $i => $match)
                    $document = str_replace($match[0], $interpolation($match[1]), $document);
        
                return $document;
            }
        ];

        foreach($patterns as $pattern => $callback)
            if (preg_match_all($pattern, $document, $matches, PREG_SET_ORDER))
                $document = $callback($document, $matches);

        return (string) $document;
    }

    /**
    *
    */
    public function execute(bool $translate = true, bool $evalutate = true): string {
        ob_start();
            $document = $translate ? $this->translate() : $this->document;

            if ($translate && $evalutate) {
                extract($this->dn_get());
                echo eval('?>' . $document);
            } else if ($evalutate)
                echo eval('?>' . $document);
            else
                echo $document;

        return ob_get_clean();
    }
}
?>