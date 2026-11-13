<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\CommunicationTemplate;
use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CommunicationTemplatesController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Communication/Templates/Index', [
            'templates' => CommunicationTemplate::orderBy('type')->orderBy('name')->get(),
        ]);
    }

    public function edit(CommunicationTemplate $template)
    {
        return Inertia::render('System/Communication/Templates/Edit', [
            'template' => $template,
        ]);
    }

    public function update(Request $request, CommunicationTemplate $template)
    {
        $validated = $request->validate([
            'subject' => 'nullable|string|max:255',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        // Strip dangerous tags/attributes (script, iframe, object, embed, inline event handlers, javascript: URIs)
        $validated['content'] = $this->sanitizeTemplateHtml($validated['content']);

        $template->update($validated);

        return redirect()->route('system.communication.templates.index')
            ->with('success', __('messages.system.templates.updated'));
    }

    /**
     * Remove executable scripts and dangerous HTML vectors from template content
     * using HTMLPurifier with a strict tag/attribute allowlist.
     */
    private function sanitizeTemplateHtml(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,pre,code,a[href|title|target],img[src|alt|width|height],table,thead,tbody,tr,th[colspan|rowspan],td[colspan|rowspan],div,span,hr');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }
}
