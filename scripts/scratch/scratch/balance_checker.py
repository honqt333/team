
import re

def check_balance(file_path):
    with open(file_path, 'r') as f:
        content = f.read()
    
    # Remove comments
    content = re.sub(r'<!--.*?-->', '', content, flags=re.DOTALL)
    
    # Find all div tags
    opens = re.findall(r'<div\b', content)
    closes = re.findall(r'</div>', content)
    
    print(f"Total <div: {len(opens)}")
    print(f"Total </div>: {len(closes)}")
    
    # Trace depth
    depth = 0
    lines = content.split('\n')
    for i, line in enumerate(lines):
        line_num = i + 1
        l_opens = len(re.findall(r'<div\b', line))
        l_closes = len(re.findall(r'</div>', line))
        depth += l_opens
        depth -= l_closes
        if depth < 0:
            print(f"Error: Negative depth at line {line_num}")
            depth = 0
    
    if depth != 0:
        print(f"Error: Final depth is {depth}")

check_balance('resources/js/Pages/WorkOrders/Show.vue')
