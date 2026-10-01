<?php

declare(strict_types=1);

namespace App\Application\Cifs;

/**
 * Construit les polylines CIFS d'une emprise à partir de ses tronçons.
 *
 * Une <polyline> CIFS décrit un seul tracé continu : Waze relie chaque point au suivant par un trait.
 * Les tronçons sont donc d'abord regroupés par connexité (une polyline par groupe, sinon deux emprises
 * éloignées seraient reliées par un trait qui n'existe pas), puis chaque groupe est parcouru en profondeur
 * en revenant sur ses pas pour rejoindre les branches : la polyline suit toujours les tronçons.
 */
final class PolylineBuilder
{
    // Deux parties du tracé dont des extrémités sont distantes de moins de ce seuil sont reliées par un pont
    // (un saut en ligne droite), ce qui tolère les petits trous d'un tracé (carrefour non couvert par la voie,
    // imprécision des données source) sans le découper en plusieurs incidents. Les ponts ne se chaînent jamais
    // par transitivité : un saut de la polyline ne dépasse donc jamais ce seuil.
    public const GAP_TOLERANCE_METERS = 50;

    // Deux extrémités distantes de moins de ce seuil sont un même point (imprécision de calcul).
    private const NODE_TOLERANCE_METERS = 1;

    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * @param string[][] $lines liste de tronçons, chaque tronçon étant une liste de points "lat lon"
     *
     * @return string[] une polyline "lat lon lat lon ..." par groupe de tronçons connectés
     */
    public function build(array $lines): array
    {
        $lines = array_values(array_filter($lines, fn (array $points) => \count($points) >= 2));

        if (!$lines) {
            return [];
        }

        $network = $this->buildNetwork($lines);
        $this->addBridges($network);

        $visited = array_fill(0, \count($network['lines']), false);
        $polylines = [];

        foreach (array_keys($lines) as $line) {
            if ($visited[$line]) {
                continue;
            }

            [$startNode, $startPoint] = $this->findStart($network, $line);
            $polyline = $this->withoutConsecutiveDuplicates($this->walk($network, $startNode, $startPoint, $visited));

            // Une polyline CIFS doit compter au moins deux points.
            if (\count($polyline) >= 2) {
                $polylines[] = implode(' ', $polyline);
            }
        }

        return $polylines;
    }

    /**
     * Construit le graphe des tronçons : les extrémités confondues sont regroupées en nœuds (union-find).
     *
     * @param string[][] $lines
     *
     * @return array{
     *     lines: string[][],
     *     bridges: array<int, bool>,
     *     ends: array<int, array{array{float, float}, array{float, float}}>,
     *     nodes: array<int, array{int, int}>,
     *     adjacency: array<int, int[]>,
     * } points de chaque tronçon, tronçons qui sont des ponts, coordonnées [lat, lon] des deux extrémités de
     *   chaque tronçon, nœuds de ses deux extrémités, et tronçons incidents à chaque nœud
     */
    private function buildNetwork(array $lines): array
    {
        $endpoints = [];

        foreach ($lines as $line => $points) {
            $endpoints[2 * $line] = $this->parsePoint($points[0]);
            $endpoints[2 * $line + 1] = $this->parsePoint($points[\count($points) - 1]);
        }

        $parent = array_keys($endpoints);

        foreach ($this->closePairs($endpoints, self::NODE_TOLERANCE_METERS) as [, $i, $j]) {
            $parent[$this->find($parent, $i)] = $this->find($parent, $j);
        }

        $network = ['lines' => $lines, 'bridges' => [], 'ends' => [], 'nodes' => [], 'adjacency' => []];

        foreach (array_keys($lines) as $line) {
            $this->addLine(
                $network,
                $line,
                [$endpoints[2 * $line], $endpoints[2 * $line + 1]],
                [$this->find($parent, 2 * $line), $this->find($parent, 2 * $line + 1)],
            );
        }

        return $network;
    }

    /**
     * Relie par des ponts les groupes de tronçons dont des extrémités sont à moins du seuil l'une de l'autre.
     * Les ponts sont choisis du plus court au plus long et seulement entre groupes pas encore reliés (arbre
     * couvrant minimal), pour qu'aucun pont ne soit inutile et qu'ils ne se chaînent pas.
     */
    private function addBridges(array &$network): void
    {
        $endpoints = [];

        foreach ($network['ends'] as $line => $ends) {
            $endpoints[2 * $line] = $ends[0];
            $endpoints[2 * $line + 1] = $ends[1];
        }

        $pairs = $this->closePairs($endpoints, self::GAP_TOLERANCE_METERS);
        usort($pairs, fn (array $a, array $b) => $a <=> $b);

        // Groupes de tronçons réellement connectés (union-find sur les nœuds).
        $group = [];

        foreach ($network['nodes'] as [$start, $end]) {
            $group[$start] ??= $start;
            $group[$end] ??= $end;
            $group[$this->find($group, $start)] = $this->find($group, $end);
        }

        foreach ($pairs as [, $i, $j]) {
            $nodeI = $network['nodes'][intdiv($i, 2)][$i % 2];
            $nodeJ = $network['nodes'][intdiv($j, 2)][$j % 2];

            if ($this->find($group, $nodeI) === $this->find($group, $nodeJ)) {
                continue;
            }

            $group[$this->find($group, $nodeI)] = $this->find($group, $nodeJ);

            $bridge = \count($network['lines']);
            $network['lines'][$bridge] = [$this->endpointLabel($network, $i), $this->endpointLabel($network, $j)];
            $network['bridges'][$bridge] = true;
            $this->addLine($network, $bridge, [$endpoints[$i], $endpoints[$j]], [$nodeI, $nodeJ]);
        }
    }

    /**
     * Le point "lat lon" d'une extrémité (indice 2 × tronçon + côté).
     */
    private function endpointLabel(array $network, int $endpoint): string
    {
        $points = $network['lines'][intdiv($endpoint, 2)];

        return $endpoint % 2 === 0 ? $points[0] : $points[\count($points) - 1];
    }

    /**
     * @param array{array{float, float}, array{float, float}} $ends
     * @param array{int, int}                                 $nodes
     */
    private function addLine(array &$network, int $line, array $ends, array $nodes): void
    {
        $network['ends'][$line] = $ends;
        $network['nodes'][$line] = $nodes;
        $network['adjacency'][$nodes[0]][] = $line;

        if ($nodes[1] !== $nodes[0]) {
            $network['adjacency'][$nodes[1]][] = $line;
        }
    }

    /**
     * Paires de points distants de moins du seuil, obtenues en balayant les points par latitude croissante pour
     * ne comparer que les paires susceptibles d'être sous le seuil.
     *
     * @param array<int, array{float, float}> $points
     *
     * @return array<array{float, int, int}> distance et indices des deux points
     */
    private function closePairs(array $points, float $toleranceMeters): array
    {
        $order = array_keys($points);
        usort($order, fn (int $a, int $b) => $points[$a][0] <=> $points[$b][0]);
        $toleranceLatDegrees = rad2deg($toleranceMeters / self::EARTH_RADIUS_METERS);
        $pairs = [];

        foreach ($order as $position => $i) {
            for ($next = $position + 1; $next < \count($order); ++$next) {
                $j = $order[$next];

                if ($points[$j][0] - $points[$i][0] > $toleranceLatDegrees) {
                    break;
                }

                $distance = $this->distanceInMeters($points[$i], $points[$j]);

                if ($distance <= $toleranceMeters) {
                    $pairs[] = [$distance, min($i, $j), max($i, $j)];
                }
            }
        }

        return $pairs;
    }

    /**
     * @param int[] $parent
     */
    private function find(array &$parent, int $i): int
    {
        while ($parent[$i] !== $i) {
            $parent[$i] = $parent[$parent[$i]];
            $i = $parent[$i];
        }

        return $i;
    }

    /**
     * Choisit le départ du parcours du groupe contenant le tronçon donné : de préférence une extrémité libre
     * (nœud de degré 1), pour qu'un tracé linéaire soit parcouru d'un bout à l'autre sans retour en arrière et en
     * conservant son sens ; à défaut un nœud de degré impair, ce qui évite au moins un retour en arrière.
     *
     * @return array{int, array{float, float}} nœud de départ et coordonnées du point de départ
     */
    private function findStart(array $network, int $firstLine): array
    {
        $componentNodes = [];
        $queue = [$network['nodes'][$firstLine][0]];

        while ($queue) {
            $node = array_shift($queue);

            if (isset($componentNodes[$node])) {
                continue;
            }

            $componentNodes[$node] = true;

            foreach ($network['adjacency'][$node] as $line) {
                array_push($queue, ...$network['nodes'][$line]);
            }
        }

        $preferences = [
            fn (int $degree) => $degree === 1,
            fn (int $degree) => $degree % 2 === 1,
        ];

        foreach ($preferences as $isPreferred) {
            foreach (array_keys($componentNodes) as $node) {
                if ($isPreferred(\count($network['adjacency'][$node]))) {
                    $line = $network['adjacency'][$node][0];
                    $side = $network['nodes'][$line][0] === $node ? 0 : 1;

                    return [$node, $network['ends'][$line][$side]];
                }
            }
        }

        return [$network['nodes'][$firstLine][0], $network['ends'][$firstLine][0]];
    }

    /**
     * Parcours en profondeur (itératif, la profondeur pouvant atteindre le nombre de tronçons) : chaque tronçon est
     * ajouté dans le sens du parcours, puis à nouveau en sens inverse une fois ses branches explorées (retour sur
     * ses pas). Les retours en arrière qui suivent le dernier tronçon parcouru à l'aller sont omis.
     *
     * @param array{float, float} $point   coordonnées du point de départ
     * @param bool[]              $visited
     *
     * @return string[] les points "lat lon" du parcours
     */
    private function walk(array $network, int $node, array $point, array &$visited): array
    {
        $walk = [];
        $forwardLength = 0;
        // Un niveau par nœud en cours d'exploration : ses tronçons candidats restants, et les points du tronçon
        // par lequel on y est arrivé (rejoués en sens inverse en le quittant).
        $stack = [[$this->candidates($network, $node, $point), []]];

        while ($stack) {
            $level = \count($stack) - 1;
            $next = null;

            while ($stack[$level][0]) {
                $candidate = array_shift($stack[$level][0]);

                if (!$visited[$candidate[2]]) {
                    $next = $candidate;
                    break;
                }
            }

            if ($next === null) {
                [, $arrivalPoints] = array_pop($stack);
                array_push($walk, ...array_reverse($arrivalPoints));
                continue;
            }

            [, , $line, $side] = $next;
            $visited[$line] = true;
            /** @var string[] $points */
            $points = $side === 0 ? $network['lines'][$line] : array_reverse($network['lines'][$line]);
            $otherSide = 1 - $side;

            array_push($walk, ...$points);
            $forwardLength = \count($walk);
            $stack[] = [$this->candidates($network, $network['nodes'][$line][$otherSide], $network['ends'][$line][$otherSide]), $points];
        }

        return \array_slice($walk, 0, $forwardLength);
    }

    /**
     * Tronçons incidents à un nœud : les vrais tronçons avant les ponts, puis par distance croissante entre le point
     * courant et leur extrémité sur ce nœud.
     *
     * @param array{float, float} $point coordonnées du point courant
     *
     * @return array<array{int, float, int, int}> pont (0 ou 1), distance, tronçon et côté (0 = départ, 1 = arrivée)
     *                                            touchant le nœud
     */
    private function candidates(array $network, int $node, array $point): array
    {
        $candidates = [];

        foreach ($network['adjacency'][$node] as $line) {
            foreach ([0, 1] as $side) {
                if ($network['nodes'][$line][$side] === $node) {
                    $candidates[] = [
                        isset($network['bridges'][$line]) ? 1 : 0,
                        $this->distanceInMeters($point, $network['ends'][$line][$side]),
                        $line,
                        $side,
                    ];
                }
            }
        }

        usort($candidates, fn (array $a, array $b) => $a <=> $b);

        return $candidates;
    }

    /**
     * @param string[] $points
     *
     * @return string[]
     */
    private function withoutConsecutiveDuplicates(array $points): array
    {
        $result = [];

        foreach ($points as $point) {
            if (!$result || $result[\count($result) - 1] !== $point) {
                $result[] = $point;
            }
        }

        return $result;
    }

    /**
     * @return array{float, float} [lat, lon]
     */
    private function parsePoint(string $point): array
    {
        [$lat, $lon] = explode(' ', trim($point), 2);

        return [(float) $lat, (float) $lon];
    }

    /**
     * @param array{float, float} $a
     * @param array{float, float} $b
     */
    private function distanceInMeters(array $a, array $b): float
    {
        // Approximation équirectangulaire, largement suffisante à l'échelle des seuils.
        $latDelta = deg2rad($b[0] - $a[0]);
        $lonDelta = deg2rad($b[1] - $a[1]) * cos(deg2rad(($a[0] + $b[0]) / 2));

        return self::EARTH_RADIUS_METERS * sqrt($latDelta ** 2 + $lonDelta ** 2);
    }
}
