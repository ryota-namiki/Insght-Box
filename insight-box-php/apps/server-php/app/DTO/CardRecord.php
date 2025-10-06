<?php

namespace App\DTO;

class CardRecord
{
    public string $id;
    public CardSummary $summary;
    public CardDetailOutputs $detail;
    public ReactionSummary $reactions;
    /** @var array<int,array{date:string,views:int,comments:int,likes:int}> */
    public array $timeseries = [];
    /** @var DepartmentRatio[] */
    public array $audience = [];

    public function __construct()
    {
        $this->summary = new CardSummary();
        $this->detail = new CardDetailOutputs();
        $this->reactions = new ReactionSummary();
    }
}
