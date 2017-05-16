<?php

    /**
     * http://ehsmeng.blogspot.com/2010/05/simple-php-xml-pretty-print-function.html
     * with the addition of URL hyperlink by Stuart Lewis
     */
    function xml_pretty_printer($xml, $indent=0)
{
    if (is_string($xml))
    {
        $xml = new SimpleXMLElement($xml);
        echo '<pre>', htmlspecialchars('<?xml version="1.0" encoding="utf-8"?>');
        xml_pretty_printer($xml);
        echo '</pre>';
        return;
    }

    echo "\n", str_pad('', $indent, ' '), '&lt;<b>', $xml->getName(), '</b>';
    foreach ($xml->attributes() as $k => $v)
    {
        if (substr($v, 0, 4) === 'http') {
            $v = '<a href="' . $v . '">' . htmlspecialchars($v) . '</a>';
        } else {
            $v = htmlspecialchars($v);
        }
        echo ' ', $k, '="<i>' . $v . '</i>"';
    }
    echo '&gt;';
    $any = false;
    foreach ($xml->children() as $k => $v)
    {
        xml_pretty_printer($v, $indent + 4);
        $any = true;
    }
    $val = (string)$xml;
    if ('' != $xml)
    {
        if (substr($val, 0, 4) === 'http') {
            $val = '<a href="' . $val . '">' . htmlspecialchars($val) . '</a>';
        } else {
            $val - htmlspecialchars($val);
        }
        echo ($any ? ("\n" . str_pad('', $indent + 4, ' ')) : ''),
             '<i>', $val, '</i>';
    }

    echo ($any ? ("\n" . str_pad('', $indent, ' ')) : ''),
         '&lt;/<b>', $xml->getName(), '</b>&gt;';
}

?>