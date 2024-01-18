<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\DBAL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

final class FirstFunction extends FunctionNode
{
    private Subselect $subselect;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->subselect = $parser->Subselect();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return '(' . $this->subselect->dispatch($sqlWalker) . ' LIMIT 1)';
    }
}
