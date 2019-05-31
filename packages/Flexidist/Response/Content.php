<?php

namespace Flexidist\Response;

class Content {

    use \traits\dotnotation;
    
    protected $template = null;
    
    public function __construct(?string $template = null, array $variables = []) {
        $this->template = $template ?: implode("\n", [
            '<!DOCTYPE html>',
            '<html {{ function.html_attributes(html.attributes) }}>',
            "\t" . '<head {{ function.html_attributes(html.head.attributes) }}>',
            "\t\t" . '<title>{{ html.head.title }}</title>',
            "\t\t" . '<meta charset="UTF-8">',
            "\t\t" . '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />',
            "\t\t" . '<meta http-equiv="X-UA-Compatible" content="ie=edge">',
            "\t\t" . '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">',
            "\t\t" . '<meta name="description" content="{{ htmlspecialchars(html.head.description) }}" />',
            "\t\t" . '{% extends("head_content.phtml") %}',
            "\n\t" . '</head>',
            "\t" . '<body {{ function.html_attributes(html.body.attributes) }}>',
            "\t\t" . '{% extends("html_content.phtml") %}',
            "\n\t" . '</body>',
            '</html>',
        ]);
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

    public static function format(string $template, int $max_recursive = 3): string {
        ob_start();
            if (preg_match('/\.phtml$/isU', $template) || (is_file($template) && is_readable($template)))
                $template = file_get_contents($template);
 
            $patterns = [
                '/{%\s+(extends)\s*\((.+)\)\s+%}/isU' => function(string $template, array $matches) use($max_recursive): string {
                    ob_start();
                        foreach ($matches as $i => $match) {
                            if ($max_recursive >= 0 && $content = @file_get_contents(eval('return ' . $match[2] . ';')))
                                $template = str_replace($match[0], self::format($content, -- $max_recursive), $template);
                            else if ($max_recursive < 0)
                                $template = str_replace($match[0], sprintf('<!-- extends(%s): Maximum "extends" nesting level reached, aborting! -->', $match[2]), $template);
                            else
                                $template = str_replace($match[0], sprintf('<!-- extends(%s): failed to open stream, no such file found. -->', $match[2]), $template);
                        }

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
                            $php_code = sprintf('<?php foreach(%s ?? [] as $%s => $%s) { ?>', self::transform($match[5]), $match[2], $match[4]);
                        else
                            $php_code = sprintf('<?php foreach(%s ?? [] as $%s) { ?>', self::transform($match[5]), $match[2]);
                
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

            foreach($patterns as $pattern => $callback)
                if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER))
                    $template = $callback($template, $matches);
            
            echo $template;

        return trim(ob_get_clean());
    }

    public static function evaluate(string $template, array $variables = []): string {
        ob_start();
            extract($variables);
            eval('?>' . self::format($template));

        return ob_get_clean();
    }

    public function preformat(string $name, string $content, bool $format_before = false) {
        if ($format_before && preg_match_all('/{%\s+(extends)\s*\((.+)\)\s+%}/isU', $this->template, $matches, PREG_SET_ORDER)) {
            ob_start();
                foreach ($matches as $i => $match) {
                    if ($max_recursive >= 0 && $content = @file_get_contents(eval('return ' . $match[2] . ';')))
                        $template = str_replace($match[0], self::format($content, -- $max_recursive), $template);
                    else if ($max_recursive < 0)
                        $template = str_replace($match[0], sprintf('<!-- extends(%s): Maximum "extends" nesting level reached, aborting! -->', $match[2]), $template);
                    else
                        $template = str_replace($match[0], sprintf('<!-- extends(%s): failed to open stream, no such file found. -->', $match[2]), $template);
                }

        		echo self::format($this->template);
        		
            $this->template = ob_get_clean();
        }
        
        $this->template = preg_replace('/\{\{!?\s+' . preg_quote($name, '/') . '\s+\}\}/isU', $content, $this->template);
    }

    public function output(bool $evaluate = true) {
        echo $evaluate ? self::evaluate($this->template, $this->dn_get()) : self::format($this->template);
    }
}
?>