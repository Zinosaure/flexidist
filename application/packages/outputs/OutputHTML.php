<?php

/**
*
*/
namespace outputs;

/**
*
*/
class OutputHTML {

    /**
    *
    */
    private $template = null;
    private $context = [];

    /**
    *
    */
    public function __construct(?string $template = null, array $context = []) {
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
            "\t\t" . '{{ html.head.content }}',
            "\n\t" . '</head>',
            "\t" . '<body {{ function.html_attributes(html.body.attributes) }}>',
            "\t\t" . '{{ html.body.content }}',
            "\n\t" . '</body>',
            '</html>',
        ]);
        $this->context = array_replace_recursive([
            'function' => [
                'html_attributes' => function (array $attributes): string {
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
        ], $context);
    }

    /**
    *
    */
    final public function get(string $name = null) {
        if (is_null($name))
            return $this->context;

        return eval('return $this->context["' . implode('"]["', explode('.', $name)) . '"];');
    }

    /**
    *
    */
    final public function set(string $name, $mixed_value, bool $trans_type = false) {
        eval('
            $attribute = &$this->context["' . implode('"]["', explode('.', $name)) . '"];
            $attribute = !is_null($attribute) ? $attribute : "";

            if ($trans_type || is_null($attribute) || gettype($attribute) == gettype($mixed_value))
                $attribute = $mixed_value;
        ');
    }

    /**
    *
    */
    final public function unset(string $name) {
        eval('unset($this->context["' . implode('"]["', explode('.', $name)) . '"]);');
    }

    /**
    *
    */
    final public function template(string $template = null): string {
        return $this->template = $template ?: $this->template;
    }

    /**
    *
    */
    final public static function express(string $expression): string {
        $variables = [];
        $double_quote_opened = false;
        $keywords = [
            '&&' => ' && ', '||' => ' || ', '&' => ' && ', '|' => ' || ', ',' => ', ', '(' => '(', ')' => ')', '!' => '!', '::' => '::', '?' => ' ?',
            ':' => ': ', '==' => ' == ', '===' => ' === ', '!=' => ' != ', '!==' => ' !== ', '>=' => ' >= ', '<=' => ' <= ', '<>' => ' <> ', '>' => '>',
            '<' => '<', '=' => ' = ', '%' => ' % ', '*' => ' * ', '/' => ' / ', '+' => ' + ', '-' => ' - '
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
    final public static function conv2php(string $template): string {
        ob_start();
            if (preg_match('/\.phtml$/isU', $template) || (is_file($template) && is_readable($template)))
                $template = file_get_contents($template);

            $patterns = [
                '/{%\s+(import|include|extends)\s*\((.+)\)\s+%}/isU' => function(string $template, array $matches): string {
                    ob_start();
                        foreach ($matches as $i => $match)
                            $template = str_replace($match[0], file_get_contents(eval('return ' . $match[2] . ';')), $template);

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
                            $php_code = sprintf('<?php foreach(%s ?? [] as $%s => $%s) { ?>', self::express($match[5]), $match[2], $match[4]);
                        else
                            $php_code = sprintf('<?php foreach(%s ?? [] as $%s) { ?>', self::express($match[5]), $match[2]);

                        $template = str_replace($match[0], $php_code, $template);
                    }

                    return $template;
                },
                '/\{%\s+(if)\s+(.+)\s+%\}/isU' => function(string $template, array $matches): string {
                    foreach($matches as $i => $match)
                        $template = str_replace($match[0], sprintf('<?php if (%s) { ?>', self::express($match[2])), $template);

                    return $template;
                },
                '/\{%\s+(else\s*if|elif)\s+(.+)\s+%\}/isU' => function(string $template, array $matches): string {
                    foreach($matches as $i => $match)
                        $template = str_replace($match[0], sprintf('<?php } else if (%s) { ?>', self::express($match[2])), $template);

                    return $template;
                },
                '/\{%\s+(else)\s+%\}/isU' => function(string $template, array $matches): string {
                    foreach ($matches as $i => $match)
                        $template = str_replace($match[0], '<?php } else { ?>', $template);

                    return $template;
                },
                '/\{\{\s+(.+)\s+\}\}/isU' => function(string $template, array $matches): string {
                    foreach ($matches as $i => $match)
                        $template = str_replace($match[0], sprintf('<?= %s ?>', self::express($match[1])), $template);

                    return $template;
                }
            ];

            foreach($patterns as $pattern => $callback)
                if (preg_match_all($pattern, $template, $matches, PREG_SET_ORDER))
                    $template = $callback($template, $matches);

            echo $template;

        return trim(ob_get_clean());
    }

    /**
    *
    */
    final public function preprocess(string $context, string $template): self {
        $this->template = preg_replace('/\{\{\s+' . preg_quote($context, '/') . '\s+\}\}/isU', $template, $this->template);

        return $this;
    }

    /**
    *
    */
    final public function render(array $context = [], array $headers = []) {
        foreach ($headers ?? ['Content-Type: text/html; charset=utf-8'] as $value)
            header($value);

        extract(array_replace_recursive((array) $this->context, $context));
        echo eval('?>' . self::conv2php($this->template));
    }

    /**
    *
    */
    final public static function evaluate(string $template, array $context = []): string {
        ob_start();
            extract($context);
            eval('?>' . self::conv2php($template));

        return ob_get_clean();
    }
}
?>