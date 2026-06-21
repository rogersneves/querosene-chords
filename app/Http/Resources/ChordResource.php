<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'version_label' => $this->version_label,
            'source' => $this->source,
            'is_default' => $this->is_default,
            'tab_content' => $this->when($this->tab_content !== null, $this->tab_content),
        ];
    }
}
