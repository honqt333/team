
import re

with open('resources/js/Pages/WorkOrders/Show.vue', 'r') as f:
    content = f.read()

# Remove comments to avoid false positives
content = re.sub(r'<!--.*?-->', '', content, flags=re.DOTALL)

opens = len(re.findall(r'<div\b', content))
closes = len(re.findall(r'</div>', content))

print(f"Total opens: {opens}")
print(f"Total closes: {closes}")
print(f"Balance: {opens - closes}")
