<?php

namespace Modules\Templates\Domain;

use Illuminate\Database\Eloquent\Model;
use Modules\Core\Domain\Concerns\HasUuid;

class ContentTemplateVersion extends Model
{
    use HasUuid;

    protected $table = 'content_template_versions';
    protected $fillable = ['template_id', 'version', 'body', 'author_id'];
    protected $casts = ['version' => 'int'];
}
