<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChordDiagramResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'strings_pattern' => $this->strings_pattern,
            'fingering' => $this->fingering,
            'fingers' => $this->fingers,
            'barre' => $this->barre,
        ];
    }
}
