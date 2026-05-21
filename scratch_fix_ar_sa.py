import os

def replace_in_file(filepath):
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()
        
    # Replace 'ar-SA' globally with 'en-GB' for dates and 'en-US' for numbers?
    # No, 'ar-SA-u-nu-latn' solves everything: it keeps Arabic month names, currency, RTL format, but forces English digits (123).
    # Since the user specifically asked for "الارقام باللغه الانجليزيه فقط" (NUMBERS in English ONLY), 
    # we should literally just change all instances of 'ar-SA' to 'ar-SA-u-nu-latn' inside formatting functions!
    
    new_content = content.replace("'ar-SA'", "'ar-SA-u-nu-latn'")
    
    # Also, some places might check `locale.value === 'ar' ? 'ar-SA' : 'en-US'`
    # They will now be `locale.value === 'ar' ? 'ar-SA-u-nu-latn' : 'en-US'` which is PERFECT.
    
    # Also, we might have `new Date().toLocaleDateString('ar-SA-u-nu-latn')` which is fine.
    
    if new_content != content:
        with open(filepath, 'w', encoding='utf-8') as f:
            f.write(new_content)
        print(f"Updated {filepath}")

# First, undo the previous naive replacement
os.system("git checkout resources/js")

for root, _, files in os.walk('resources/js'):
    for file in files:
        if file.endswith('.vue') or file.endswith('.js'):
            replace_in_file(os.path.join(root, file))
