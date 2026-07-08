#!/usr/bin/env node

import fs from 'fs';
import { parse } from 'vue/compiler-sfc';

const filePath = process.argv[2];
if (!filePath) {
    console.error("JSON_ERROR: Please provide a file path.");
    process.exit(1);
}

try {
    const code = fs.readFileSync(filePath, 'utf-8');
    const { descriptor } = parse(code);
    
    const result = {
        hasScriptSetup: !!descriptor.scriptSetup,
        scriptSetupContent: descriptor.scriptSetup ? descriptor.scriptSetup.content : '',
        templateContent: descriptor.template ? descriptor.template.content : '',
        props: [],
        emits: [],
        composables: [],
        rawElements: []
    };

    if (result.hasScriptSetup) {
        const content = result.scriptSetupContent;
        
        // 1. Detect props definition (macro or typescript type definition)
        const propsRegex = /defineProps\s*(?:<[^>]+>)?\s*\(\s*([\s\S]*?)\s*\)/g;
        let match;
        while ((match = propsRegex.exec(content)) !== null) {
            result.props.push(match[1].trim());
        }
        
        // 2. Detect emits definition
        const emitsRegex = /defineEmits\s*(?:<[^>]+>)?\s*\(\s*([\s\S]*?)\s*\)/g;
        while ((match = emitsRegex.exec(content)) !== null) {
            result.emits.push(match[1].trim());
        }
        
        // 3. Detect Composables (use*)
        const composableRegex = /\b(use[A-Z]\w*)\b/g;
        while ((match = composableRegex.exec(content)) !== null) {
            if (!result.composables.includes(match[1])) {
                result.composables.push(match[1]);
            }
        }
    }

    if (descriptor.template) {
        const template = descriptor.template.content;
        
        const elementsToCheck = [
            { tag: 'button', component: '(?:PrimaryButton|SecondaryButton|DangerButton|BackButton)' },
            { tag: 'input', component: 'TextInput' },
            { tag: 'select', component: 'SelectInput' }
        ];

        elementsToCheck.forEach(el => {
            const rawRegex = new RegExp(`<${el.tag}\\b[^>]*>`, 'gi');
            const compRegex = new RegExp(`<${el.component}\\b[^>]*>`, 'gi');
            
            let rawMatches = (template.match(rawRegex) || []).length;
            let compMatches = (template.match(compRegex) || []).length;
            
            if (rawMatches > 0 || compMatches > 0) {
                result.rawElements.push({
                    name: el.tag,
                    component: el.component,
                    compliantCount: compMatches,
                    violationCount: rawMatches
                });
            }
        });
    }

    console.log(JSON.stringify(result, null, 2));
} catch (err) {
    console.error("JSON_ERROR: " + err.message);
    process.exit(1);
}
