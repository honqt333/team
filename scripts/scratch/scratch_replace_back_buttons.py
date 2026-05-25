import os
import re

directory = 'resources/js/Pages'

# We'll use a regex that matches a typical back button block
# The block starts with <Link or <Tooltip and contains $t('common.back')
# It usually ends with </Link> or </Tooltip>

def update_file(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    original_content = content

    # Strategy: Find `<Link` that contains `:href="SOME_HREF"` and `:title="$t('common.back')"`
    # or wrapped in `<Tooltip` that contains `$t('common.back')`
    
    # Pattern for <Link ... :title="$t('common.back')" ...> ... </Link>
    # capturing the href
    p1 = re.compile(r'<Link[^>]*:href="([^"]+)"[^>]*:title="\$t\(\'common\.back\'\)"[^>]*>.*?<\/Link>', re.DOTALL)
    
    # Pattern for <Tooltip ... :text="$t('common.back')" ...> <Link ... :href="SOME_HREF" ...> ... </Link> </Tooltip>
    p2 = re.compile(r'<Tooltip[^>]*:text="\$t\(\'common\.back\'\)"[^>]*>\s*<Link[^>]*:href="([^"]+)"[^>]*>.*?<\/Link>\s*<\/Tooltip>', re.DOTALL)

    # Some might use :content="$t('common.back')"
    p3 = re.compile(r'<Tooltip[^>]*:content="\$t\(\'common\.back\'\)"[^>]*>\s*<Link[^>]*:href="([^"]+)"[^>]*>.*?<\/Link>\s*<\/Tooltip>', re.DOTALL)

    # Some might have the title attribute before href
    p4 = re.compile(r'<Link[^>]*:title="\$t\(\'common\.back\'\)"[^>]*:href="([^"]+)"[^>]*>.*?<\/Link>', re.DOTALL)
    
    content = p1.sub(r'<BackButton :href="\1" />', content)
    content = p2.sub(r'<BackButton :href="\1" />', content)
    content = p3.sub(r'<BackButton :href="\1" />', content)
    content = p4.sub(r'<BackButton :href="\1" />', content)

    # If it was changed, we must ensure BackButton is imported
    if content != original_content:
        # Check if BackButton is already imported
        if 'import BackButton from' not in content:
            # Add it to script setup
            content = re.sub(r'(<script setup>[\s\S]*?)(import )', r'\1import BackButton from \'@/Components/BackButton.vue\';\n\2', content, count=1)
        
        with open(filepath, 'w') as f:
            f.write(content)
        print(f"Updated: {filepath}")

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith('.vue'):
            update_file(os.path.join(root, file))
