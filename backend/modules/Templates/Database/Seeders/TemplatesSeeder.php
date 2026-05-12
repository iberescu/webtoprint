<?php

namespace Modules\Templates\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Templates\Domain\ContentTemplate;

/**
 * Seeds the five default content templates used by the Distribution module
 * when it packages a production job. The defaults are intentionally generic
 * — every customer install is expected to edit them in /admin/content-templates
 * to match their MIS / pressroom conventions.
 *
 * Variable surface available to all templates (Blade + Twig):
 *
 *   $job        Modules\Distribution\Domain\Models\ProductionJob
 *   $config     array — the configuration_snapshot_json (format, paper, …)
 *   $company    array — flattened company.* settings (name, address, …)
 *   $artwork    array|null — { path, mime, pages, width_mm, height_mm }
 *   $now        Carbon\Carbon
 *
 * "raw" templates (folder_name / file_name) use a tiny `{var.path}` syntax
 * with dot-path access, no expressions.
 */
class TemplatesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $tpl) {
            ContentTemplate::updateOrCreate(
                ['key' => $tpl['key']],
                $tpl,
            );
        }
    }

    /**
     * @return array<int,array{key:string,kind:string,engine:string,body:string,metadata_json:array}>
     */
    private function templates(): array
    {
        return [
            [
                'key'  => 'default.jobsheet',
                'kind' => 'jobsheet_pdf',
                'engine' => 'blade',
                'metadata_json' => [
                    'description' => 'A4 production jobsheet rendered by Dompdf. Tweak headers, branding and field labels to match what your pressroom expects.',
                    'page_size' => 'A4',
                ],
                'body' => $this->jobsheet(),
            ],
            [
                'key'  => 'default.jdf',
                'kind' => 'jdf_xml',
                'engine' => 'twig',
                'metadata_json' => [
                    'description' => 'CIP4 JDF 1.4 minimal Product node. Most MIS systems accept this as a starting point; richer fields (RunList, Media, Layout) can be added as your prepress workflow demands.',
                    'jdf_version' => '1.4',
                ],
                'body' => $this->jdf(),
            ],
            [
                'key'  => 'default.mxml',
                'kind' => 'mxml_xml',
                'engine' => 'twig',
                'metadata_json' => [
                    'description' => 'Vendor-neutral manufacturing XML. Mirrors the JDF data with a flatter schema typical of small/mid MIS imports (Optimus, Avanti Slingshot, EFI Pace).',
                ],
                'body' => $this->mxml(),
            ],
            [
                'key'  => 'default.file_name',
                'kind' => 'file_name',
                'engine' => 'raw',
                'metadata_json' => [
                    'description' => 'File-name template used inside the production package ZIP. Stick to ASCII + dashes — many press RIPs reject spaces and unicode.',
                ],
                'body' => '{job.job_number}-{slug}.pdf',
            ],
            [
                'key'  => 'default.folder_name',
                'kind' => 'folder_name',
                'engine' => 'raw',
                'metadata_json' => [
                    'description' => 'Directory layout inside the package ZIP. Sorting by date keeps daily exports tidy when shop-floor staff browse via Finder/Explorer.',
                ],
                'body' => '{now.Ymd}/{job.external_order_ref}-{job.job_number}',
            ],
        ];
    }

    private function jobsheet(): string
    {
        return <<<'BLADE'
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Jobsheet — {{ $job->job_number }}</title>
<style>
  @page { size: A4; margin: 14mm 14mm 18mm 14mm; }
  body  { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #1a1a1a; }
  h1    { font-size: 16pt; margin: 0 0 2mm 0; }
  .muted{ color: #666; }
  .grid { display: table; width: 100%; border-collapse: collapse; margin: 4mm 0; }
  .row  { display: table-row; }
  .cell { display: table-cell; padding: 2mm 3mm; border: 0.3pt solid #ccc; vertical-align: top; }
  .cell.k { background: #f5f5f5; font-weight: bold; width: 28%; }
  .barcode { font-family: 'libre barcode 128', monospace; font-size: 38pt; letter-spacing: 0; }
  .header { display: table; width: 100%; }
  .header .left  { display: table-cell; }
  .header .right { display: table-cell; text-align: right; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 2mm 3mm; border-bottom: 0.3pt solid #ddd; text-align: left; }
  th    { background: #f5f5f5; }
  .qr   { font-family: monospace; font-size: 8pt; }
  footer{ position: fixed; bottom: 8mm; left: 14mm; right: 14mm;
          font-size: 8pt; color: #888; border-top: 0.3pt solid #ddd; padding-top: 2mm; }
</style>
</head>
<body>

<div class="header">
  <div class="left">
    <h1>{{ $company['name'] ?? 'Web-to-Print' }}</h1>
    <div class="muted">{{ $company['address'] ?? '' }}</div>
  </div>
  <div class="right">
    <div class="muted">Jobsheet</div>
    <div style="font-size: 14pt; font-weight: bold;">{{ $job->job_number }}</div>
    <div class="muted">{{ $now->format('Y-m-d H:i') }}</div>
  </div>
</div>

<h2 style="margin-top: 6mm;">{{ $job->product_name }}</h2>

<div class="grid">
  <div class="row">
    <div class="cell k">External order</div>
    <div class="cell">{{ $job->source }} · {{ $job->external_order_ref ?: '—' }}</div>
  </div>
  <div class="row">
    <div class="cell k">Item ref</div>
    <div class="cell">{{ $job->external_order_item_ref ?: '—' }}</div>
  </div>
  <div class="row">
    <div class="cell k">Status</div>
    <div class="cell"><strong>{{ strtoupper($job->status) }}</strong></div>
  </div>
</div>

<h3>Configuration</h3>
<table>
  <thead><tr><th>Option</th><th>Value</th></tr></thead>
  <tbody>
  @foreach (($config ?? []) as $k => $v)
    <tr><td>{{ ucfirst(str_replace('_', ' ', $k)) }}</td><td>{{ is_array($v) ? json_encode($v) : $v }}</td></tr>
  @endforeach
  </tbody>
</table>

@if (! empty($artwork))
<h3 style="margin-top: 6mm;">Artwork</h3>
<div class="grid">
  <div class="row"><div class="cell k">File</div><div class="cell">{{ $artwork['path'] ?? '—' }}</div></div>
  <div class="row"><div class="cell k">Pages</div><div class="cell">{{ $artwork['pages'] ?? '—' }}</div></div>
  <div class="row"><div class="cell k">Trim size</div><div class="cell">{{ ($artwork['width_mm'] ?? '?') }} × {{ ($artwork['height_mm'] ?? '?') }} mm</div></div>
  <div class="row"><div class="cell k">MIME</div><div class="cell">{{ $artwork['mime'] ?? '—' }}</div></div>
</div>
@endif

<h3 style="margin-top: 6mm;">Press-floor barcode</h3>
<div class="barcode">*{{ $job->job_number }}*</div>
<div class="qr">Scan to load this job in the press RIP.</div>

<footer>
  Generated by the Web-to-Print platform on {{ $now->toIso8601String() }}.
  This sheet is the source of truth — do not reprint a job from cached settings.
</footer>

</body>
</html>
BLADE;
    }

    private function jdf(): string
    {
        return <<<'TWIG'
<?xml version="1.0" encoding="UTF-8"?>
{#
  CIP4 JDF 1.4 minimal Product node. This is the lowest level the spec
  accepts without complaints from common MIS importers (Heidelberg Prinect,
  Kodak Prinergy, etc.). Customise the structure here once you know your
  pressroom's exact JDF shape.
#}
<JDF
  xmlns="http://www.CIP4.org/JDFSchema_1_1"
  ID="{{ job.id }}"
  JobID="{{ job.job_number }}"
  Type="Product"
  Status="Waiting"
  Version="1.4"
  DescriptiveName="{{ job.product_name|e }}">

  <NodeInfo>
    <CustomerInfo CustomerOrderID="{{ job.external_order_ref|e }}"
                  CustomerJobName="{{ job.product_name|e }}"/>
  </NodeInfo>

  <ResourcePool>
    <Component
      ID="C-{{ job.id }}"
      Class="Quantity"
      Status="Available"
      DescriptiveName="{{ job.product_name|e }}"
      ProductType="{{ config.product_type ?? 'Brochure' }}"
      Amount="{{ config.quantity ?? 1 }}"/>

    {% if artwork %}
    <RunList
      ID="R-{{ job.id }}"
      Class="Parameter"
      Status="Available"
      Pages="{{ artwork.pages ?? 1 }}"
      NPage="{{ artwork.pages ?? 1 }}">
      <LayoutElement>
        <FileSpec MimeType="{{ artwork.mime|default('application/pdf') }}"
                  URL="{{ artwork.path|e }}"/>
      </LayoutElement>
    </RunList>
    {% endif %}

    <Media
      ID="M-{{ job.id }}"
      Class="Consumable"
      Status="Available"
      MediaType="Paper"
      Dimension="{{ artwork.width_mm ?? 210 }} {{ artwork.height_mm ?? 297 }}"
      Weight="{{ config.paper_gsm ?? 170 }}"/>
  </ResourcePool>

  <ResourceLinkPool>
    <ComponentLink rRef="C-{{ job.id }}" Usage="Output"/>
    {% if artwork %}<RunListLink rRef="R-{{ job.id }}" Usage="Input"/>{% endif %}
    <MediaLink   rRef="M-{{ job.id }}" Usage="Input"/>
  </ResourceLinkPool>
</JDF>
TWIG;
    }

    private function mxml(): string
    {
        return <<<'TWIG'
<?xml version="1.0" encoding="UTF-8"?>
{# Generic Manufacturing XML — flat schema suitable for MIS imports that
   don't speak full JDF. Each <Field> is name/value to maximise compatibility. #}
<ManufacturingOrder>
  <Header>
    <JobNumber>{{ job.job_number }}</JobNumber>
    <ExternalOrder source="{{ job.source }}">{{ job.external_order_ref }}</ExternalOrder>
    <ExternalItem>{{ job.external_order_item_ref }}</ExternalItem>
    <Status>{{ job.status }}</Status>
    <CreatedAt>{{ now.toIso8601String() }}</CreatedAt>
  </Header>

  <Product>
    <Name>{{ job.product_name|e }}</Name>
    <Quantity>{{ config.quantity ?? 1 }}</Quantity>

    <Specification>
      {% for key, value in config %}
      <Field name="{{ key }}">{{ value is iterable ? value|json_encode : value }}</Field>
      {% endfor %}
    </Specification>
  </Product>

  {% if artwork %}
  <Artwork>
    <File>{{ artwork.path|e }}</File>
    <Pages>{{ artwork.pages ?? 1 }}</Pages>
    <TrimSize unit="mm">
      <Width>{{ artwork.width_mm ?? 0 }}</Width>
      <Height>{{ artwork.height_mm ?? 0 }}</Height>
    </TrimSize>
    <MimeType>{{ artwork.mime|default('application/pdf') }}</MimeType>
  </Artwork>
  {% endif %}

  <Production>
    <Press>{{ config.press ?? '' }}</Press>
    <Substrate gsm="{{ config.paper_gsm ?? '' }}">{{ config.paper ?? '' }}</Substrate>
    <ColorMode>{{ config.colors ?? '' }}</ColorMode>
    <Finishing>{{ config.refinement ?? 'none' }}</Finishing>
  </Production>
</ManufacturingOrder>
TWIG;
    }
}
