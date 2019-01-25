<?php

namespace App\Service\Content;

use App\Service\Data\CsvReader;
use PhpParser\Error;
use PhpParser\ParserFactory;

class DescriptionFormatter
{
    /** @var string|array */
    public $description;
    /** @var string */
    public $descriptionSimple;
    /** @var array */
    public $colours = [];
    
    public function format(string $description)
    {
        foreach (CsvReader::Get(__DIR__ . '/UIColor.csv') as $row) {
            $id = $row['key'];
            [$colourA, $colourB] = $row;
            $this->colours[$id] = $colourA;
        }
        
        $this->description = $description;

        $this->formatColors();
        $this->formatLogic();
        $this->formatHtml();
        $this->formatSimpleDescription();

        return [
            $this->description,
            $this->descriptionSimple
        ];
    }

    /**
     * Replace color entries with hex value and add SPAN placeholders
     */
    public function formatColors()
    {
        // remove 73 because not sure what it is right now
        $this->description = preg_replace("#<UIGlow>(.*?)</UIGlow>#is", null, $this->description);
        
        // replace 72 closing with a reset
        $this->description = str_ireplace('<UIForeground>01</UIForeground>', '__ENDSPAN__', $this->description);
        
        // replace all colour entries with hex values
        preg_match_all("#<UIForeground>(.*?)</UIForeground>#is", $this->description, $matches);
        foreach($matches[1] as $code) {
            // we only care for last 2 bytes
            $color = substr($code, -4);
    
            // convert hex to decimal
            $color = hexdec($color);
            
            // grab colour code
            $color = $this->colours[$color] ?? 0;
            
            // convert dec to hex
            $color = dechex($color);
    
            // ensure its padded correctly
            $color = str_pad($color, 6, '0', STR_PAD_LEFT);
            
            // ignore alpha and just take the first 6
            $color = substr($color, 0, 6);
            $this->description = str_ireplace("<UIForeground>{$code}</UIForeground>", "--START_SPAN style=\"color:#{$color};\"--", $this->description);
        }
    }

    /**
     * Format any placeholder html into real html
     */
    public function formatHtml()
    {
        // convert to an array
        $this->description = json_decode(json_encode($this->description), true);

        // replace placeholders
        array_walk_recursive($this->description, function (&$value, $index){
            $value = str_ireplace(
                ['--START_SPAN', '__ENDSPAN__', '--', "<SoftHyphen/>", "<Indent/>"],
                ['<span', '</span>', '>', null, null],
                $value
            );
        });
    }
    
    /**
     * Format a simple description
     */
    public function formatSimpleDescription()
    {
        $simple = $this->formSimpleDescriptionRecursive($this->description, []);
        $simple = implode(" ", $simple);
        $this->descriptionSimple = $simple;
    }
    
    /**
     * (Recursive) Format a simple description
     */
    public function formSimpleDescriptionRecursive($desc, $arr, $action = 'true')
    {
        if (is_array($desc)) {
            foreach($desc as $i => $line) {
                if (is_array($line)) {
                    $statement = $line[$action];
                    $arr = $this->formSimpleDescriptionRecursive($statement, $arr, $action);
                } else {
                    $arr[] = $line;
                }
            }
        } else {
            $arr[] = $desc;
        }
        
        return $arr;
    }

    /**
     * Formats the description into logic
     */
    public function formatLogic()
    {
        // make it easier to split up each line when logic begins and ends
        $this->description = str_ireplace(['<','>'], ['###<','>###'], $this->description);

        // split logic by each code block
        $lines = array_values(array_filter(explode('###', $this->description)));

        /**
         * Convert logic into PHP code, this is so we can parse it using an abstract tree syntax parser.
         */
        foreach($lines as $i => $line) {
            $state = false;
            $state = substr($line, 0, 3) === '<If' ? 'if_open' : $state;
            $state = substr($line, 0, 4) === '</If' ? 'if_close' : $state;
            $state = $line === '<Else/>' ? 'if_else' : $state;
            switch($state) {
                case 'if_open':
                    $line = $this->convertIfOpenToPhpLogic($line);
                    break;
                case 'if_close':
                    $line = '<?php } ?>';
                    break;
                case 'if_else':
                    $line = '<?php } else { ?>';
                    break;
            }

            // replace line with formatted version
            $lines[$i] = $line;
        }
        try {
            $strubg = implode("", $lines);
            
            $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
            $logic = $parser->parse($strubg);
            
            // strips down everything
            $json = json_decode(json_encode($logic));
            // format the description into a minified format
            
            $json = $this->simpleJsonFormat($json);
            
            // fin
            $this->description = $json;
        } catch (Error $error) {
            die("\n\narse error: {$error->getMessage()}\n\n");
        }
    }

    /**
     * Builds a simple json format
     */
    public function simpleJsonFormat($json)
    {
        foreach($json as $i => $code) {
            switch($code->nodeType) {
                case 'Stmt_InlineHTML';
                    $json[$i] = trim($code->value);
                    break;
                case 'Stmt_If':
                    $json[$i] = $this->simpleJsonIfStatementFormat($code);
                    break;
            }
        }

        return $json;
    }

    /**
     * Simple json formatter
     */
    public function simpleJsonIfStatementFormat($code)
    {
        $operands = [
            'Expr_BinaryOp_GreaterOrEqual' => '>=',
            'Expr_BinaryOp_SmallerOrEqual' => '<=',
            'Expr_BinaryOp_NotEqual' => '!=',
            'Expr_BinaryOp_Equal' => '==',
        ];
        
        $true   = $this->simpleJsonConditionFormat($code->stmts[0]);
        $false  = $this->simpleJsonConditionFormat($code->else);

        $stmt = (Object)[
            'condition' => (Object)[
                'left' => $code->cond->left->name,
                'right' => $code->cond->right->value,
                'operator' => $operands[$code->cond->nodeType],
            ],
            'true' => $true ? [ $true ] : null,
            'false' => $false ? [ $false ] : null,
        ];

        return $stmt;
    }

    /**
     * Format if statement condition
     */
    public function simpleJsonConditionFormat($stmt)
    {
        // if not type is an if statement, recursively throw it back
        if ($stmt->nodeType == 'Stmt_If')
        {
            $stmt = $this->simpleJsonIfStatementFormat($stmt);
        }
        // if node type is an else statement, handle each one individually
        else if ($stmt->nodeType == 'Stmt_Else') {
            $stmt = $this->simpleJsonFormat($stmt->stmts);
            // if statement is just a string, set that
            if (isset($stmt[0]) && is_string($stmt[0]))
            {
                $stmt = trim($stmt[0]);
            }
            // if no statements, return empty
            elseif (empty($stmt))
            {
                $stmt = '';
            }
        }
        // if node type an inline html, use that value
        else if ($stmt->nodeType == 'Stmt_InlineHTML')
        {
            $stmt = trim($stmt->value);
        }
        // if no statements, return empty
        else if (empty($stmt->stmts))
        {
            $stmt = '';
        }

        return $stmt;
    }

    /**
     * Convert an "if" line to PHP logic
     */
    public function convertIfOpenToPhpLogic($line)
    {
        // Thank @Hez for this!
        preg_match_all('/\<If\((?P<operator>\w+)\((?P<parameter>\w+)\((?P<x>\d+)\),(?P<y>\d+)\)\)>/', $line, $matches);

        $statement = (Object)[
            'operator' => $matches['operator'][0],
            'parameter' => $matches['parameter'][0],
            'x' => $matches['x'][0],
            'y' => $matches['y'][0]
        ];

        $operators = [
            'GreaterThanOrEqualTo' => '>=',
            'LessThanOrEqualTo' => '<=',
            'NotEqual' => '!=',
            'Equal' => '==',
        ];

        $s = (Object)[
            'left'      => $this->getPlayerParameterContext($statement->parameter, $statement->x),
            'operator'  => $operators[$statement->operator],
            'right'     => $statement->y,
        ];

        return sprintf(
            '<?php if ($%s %s %s) { ?>',
            $s->left,
            $s->operator,
            $s->right
        );
    }

    /**
     * List of PlayerParameters
     */
    public function getPlayerParameterContext($param, $value)
    {
        $key = "{$param}_{$value}";
        switch($key) {
            default: return "UNKNOWN_{$key}";
            case 'PlayerParameter_68': return 'class_job_id';
            case 'PlayerParameter_69': return 'class_job_level';
            case 'PlayerParameter_70': return 'starting_city_id';
            case 'PlayerParameter_71': return 'race';
            case 'PlayerParameter_72': return 'class_job_level';
        }
    }
}
