<?php declare(strict_types=1);

namespace Hyva\Admin\ViewModel\HyvaGrid;

interface GridActionInterface
{
    public function getId(): string;

    public function getUrl(): string;

    public function getLabel(): string;

    /**
     * @param RowInterface $row
     * @return string[]
     */
    public function getParams(RowInterface $row): array;
}
