<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\PostGIS\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class ST_ApproximateMedialAxis extends FunctionNode
{
    protected array $expressions = [];

    public function parse(Parser $parser): void
    {
        // Signature: `geometry ST_ApproximateMedialAxis(geometry geom);`
        // https://postgis.net/docs/ST_ApproximateMedialAxis.html
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->expressions[] = $parser->ArithmeticFactor();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $arguments = [];

        /** @var Node $expression */
        foreach ($this->expressions as $expression) {
            $arguments[] = $expression->dispatch($sqlWalker);
        }

        return 'ST_ApproximateMedialAxis(' . implode(', ', $arguments) . ')';
    }
}
