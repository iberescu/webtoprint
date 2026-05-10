<?php

namespace Modules\Templates\Domain;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Domain\Concerns\HasUuid;

class ContentTemplate extends Model
{
    use HasUuid;

    protected $table = 'content_templates';
    protected $fillable = ['key', 'kind', 'body', 'engine', 'metadata_json'];
    protected $casts = ['metadata_json' => 'array'];

    public function versions(): HasMany
    {
        return $this->hasMany(ContentTemplateVersion::class, 'template_id')->orderByDesc('version');
    }

    public function snapshot(?string $authorId = null): ContentTemplateVersion
    {
        $next = ($this->versions()->max('version') ?? 0) + 1;
        return $this->versions()->create([
            'version' => $next,
            'body' => $this->body,
            'author_id' => $authorId,
        ]);
    }
}
