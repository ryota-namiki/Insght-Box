<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\CardRepository;

class BoardController extends Controller
{
    public function index(CardRepository $repo)
    {
        $all = $repo->read();
        $cards = [];
        
        foreach ($all as $card) {
            $summary = $card['summary'] ?? [];
            $summary['position'] = $card['position'] ?? ['x' => 0, 'y' => 0];
            $summary['reactions'] = $card['reactions'] ?? ['likes' => 0, 'comments' => 0, 'views' => 0];
            $cards[] = $summary;
        }
        
        return view('board.index', compact('cards'));
    }
}
