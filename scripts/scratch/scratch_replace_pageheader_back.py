import os
import re

directory = 'resources/js/Pages'

def update_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    original_content = content

    # Find the <template #back> block and replace its inner <Link> with <BackButton>
    # <template #back>
    #     <Link href="/app/settings" ... > ... </Link>
    # </template>
    
    p = re.compile(r'(<template #back>\s*)<Link\s+href="([^"]+)"[\s\S]*?<\/Link>(\s*<\/template>)', re.DOTALL)
    content = p.sub(r'\1<BackButton href="\2" />\3', content)

    # Some might use :href="..."
    p2 = re.compile(r'(<template #back>\s*)<Link\s+:href="([^"]+)"[\s\S]*?<\/Link>(\s*<\/template>)', re.DOTALL)
    content = p2.sub(r'\1<BackButton :href="\2" />\3', content)

    if content != original_content:
        if 'import BackButton from' not in content:
            content = re.sub(r'(<script setup>[\s\S]*?)(import )', r'\1import BackButton from \'@/Components/BackButton.vue\';\n\2', content, count=1)
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated: {filepath}")

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith('.vue'):
            update_file(os.path.join(root, file))
