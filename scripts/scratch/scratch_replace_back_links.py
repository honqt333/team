import os
import re

files_to_check = [
    'resources/js/Pages/WorkOrders/Index.vue',
    'resources/js/Pages/WorkOrders/Show.vue',
    'resources/js/Pages/Quotes/Index.vue',
    'resources/js/Pages/Quotes/Show.vue'
]

for filepath in files_to_check:
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # We need to find the raw <Link> that acts as a back button and replace it with <BackButton>
    # Since they use :title="$t('common.back')", we can search for that.
    
    # In WorkOrders/Show.vue:
    # <Link :href="backUrl"
    #     :title="$t('common.back')"
    #     class="p-2.5 ... text-indigo-600">
    #     <svg ...>
    #         <path ... />
    #     </svg>
    # </Link>
    
    pattern = re.compile(r'<Link\s+:href="([^"]+)"\s*:title="\$t\(\'common\.back\'\)".*?</Link>', re.DOTALL)
    
    def replacer(match):
        href_val = match.group(1)
        return f'<BackButton :href="{href_val}" />'

    new_content = pattern.sub(replacer, content)
    
    # Add import BackButton if not exists
    if '<BackButton' in new_content and 'import BackButton' not in new_content:
        # find the first import in <script setup>
        new_content = new_content.replace('<script setup>\n', "<script setup>\nimport BackButton from '@/Components/BackButton.vue';\n")
        
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")

