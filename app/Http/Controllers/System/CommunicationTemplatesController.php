<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\CommunicationTemplate;
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

        $template->update($validated);

        return redirect()->route('system.communication.templates.index')
            ->with('success', 'تم تحديث القالب بنجاح');
    }
}
