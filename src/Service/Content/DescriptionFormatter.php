<?php

namespace App\Service\Content;

use PhpParser\Error;
use PhpParser\ParserFactory;

class DescriptionFormatter
{
    /** @var string|array */
    public $description;
    /** @var string */
    public $descriptionTrue = [];
    /** @var string */
    public $descriptionFalse = [];
    /** @var array */
    public $colours = [];
    
    public function format(string $description)
    {
        $uiColors = file_get_contents(__DIR__ . '/UIColor.csv');
        $uiColors = explode(PHP_EOL, $uiColors);

        foreach ($uiColors as $row) {
            $line    = explode(',', $row);
            $id      = $line[0] ?? null;
            $colourA = $line[2] ?? null;
            
            if (empty($id) || empty($colourA)) {
                continue;
            }
            
            $this->colours[$id] = $colourA;
        }
        
        $this->description = $description;

        $this->formatColors();
        $this->formatLogic();
        $this->formatHtml();
        $this->formatSimpleDescription();

        return [
            $this->description,
            $this->descriptionTrue,
            $this->descriptionFalse,
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
    
            // ensure its padded correctly (its padded to 8 to include alpha)
            $color = str_pad($color, 8, '0', STR_PAD_LEFT);
            
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
        
        $this->descriptionTrue  = implode(" ", $this->formSimpleDescriptionRecursive($this->description, []));
        $this->descriptionFalse = implode(" ", $this->formSimpleDescriptionRecursive($this->description, [], 'false'));
    }
    
    /**
     * (Recursive) Format a simple description
     */
    public function formSimpleDescriptionRecursive($desc, $arr, $action = 'true')
    {
        if (is_array($desc)) {
            foreach($desc as $i => $line) {
                if (is_array($line)) {
                    $statement = $line[$action] ?? null;
                    if ($statement === null) {
                        continue;
                    }
                    
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
            die("\n\n Parse error: {$error->getMessage()}\n\n");
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
            'Expr_Variable' => '==',
        ];
        
        $true   = $this->simpleJsonConditionFormat($code->stmts[0]);
        $false  = $this->simpleJsonConditionFormat($code->else);
        
        // if no right value, its a single variable condition
        if (!isset($code->cond->right->value)) {
            return (Object)[
                'condition' => [
                    'left' => $code->cond->name ?? '[no_left]',
                    'right' => 'true',
                    'operator' => '==',
                ],
                'true' => $true ? [ $true ] : null,
                'false' => $false ? [ $false ] : null,
            ];
        }
        
        return (Object)[
            'condition' => (Object)[
                'left' => $code->cond->left->name ?? '[no_left]',
                'right' => $code->cond->right->value ?? '[no_right]',
                'operator' => $operands[$code->cond->nodeType],
            ],
            'true' => $true ? [ $true ] : null,
            'false' => $false ? [ $false ] : null,
        ];
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
            $stmt = nl2br($stmt->value);
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

        // if there is no comparison condition, then its just a variable state condition
        if (!isset($matches['y'][0])) {
            preg_match_all('/\<If\((?P<parameter>\w+)\((?P<condition>\d+)\)\)>/', $line, $matches);
    
            $statement = (Object)[
                'operator' => 'IsTrue',
                'parameter' => $matches['parameter'][0],
                'condition' => $matches['condition'][0],
            ];
    
            $condition = $this->getPlayerParameterContext($statement->parameter, $statement->condition);
    
            return sprintf(
                '<?php if ($%s) { ?>',
                $condition
            );
            
        } else {
            $statement = (Object)[
                'operator' => $matches['operator'][0],
                'parameter' => $matches['parameter'][0],
                'x' => $matches['x'][0],
                'y' => $matches['y'][0]
            ];
        }

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
        if (empty($param)) {
            return 'EmptyParam';
        }
        
        $playerParameters = [
            0  => 'reset',
            1  => 'reset_bold',
            4  => 'is_woman',
            5  => 'action_target_is_woman',

            11 => 'in_game_hours',
            12 => 'in_game_minutes',
            13 => 'say_color',
            14 => 'shout_color',
            15 => 'tell_color',
            16 => 'party_color',
            18 => 'linkshell_1_color',
            19 => 'linkshell_2_color',
            20 => 'linkshell_3_color',
            21 => 'linkshell_4_color',
            22 => 'linkshell_5_color',
            23 => 'linkshell_6_color',
            24 => 'linkshell_7_color',
            25 => 'linkshell_8_color',
            26 => 'free_company_color',
            30 => 'custom_emotes_color',
            31 => 'standard_emotes_color',

            68 => 'class_job_id',
            69 => 'class_job_level',
            70 => 'starting_city_id',
            71 => 'race',
            72 => 'class_job_level',
            
            216 => 'milliseconds',
            217 => 'seconds',
            218 => 'minutes',
            219 => 'hours',
            220 => 'day',
            221 => 'week_day',
            222 => 'month',
            223 => 'year',
            224 => '>=',
            225 => '>',
            226 => '<=',
            227 => '<',
            228 => '==',
            229 => '!=',
            235 => 'color',
            236 => 'reset_color',
        ];
        
        return $playerParameters[$value] ?? "unknown_{$param}_{$value}";
    }
}
