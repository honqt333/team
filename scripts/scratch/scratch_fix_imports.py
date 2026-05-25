import os

directory = 'resources/js/Pages'

for root, _, files in os.walk(directory):
    for file in files:
        if file.endswith('.vue'):
            path = os.path.join(root, file)
            with open(path, 'r') as f:
                content = f.read()
                
            if r"\'@/Components/BackButton.vue\'" in content:
                content = content.replace(r"\'@/Components/BackButton.vue\'", "'@/Components/BackButton.vue'")
                with open(path, 'w') as f:
                    f.write(content)
                print(f"Fixed: {path}")
