
import re

with open('resources/js/Pages/WorkOrders/Show.vue', 'r') as f:
    lines = f.readlines()

stack = []
results = []

for i, line in enumerate(lines):
    line_num = i + 1
    
    # Simple div tag matcher
    opens = re.findall(r'<div\b', line)
    closes = re.findall(r'</div>', line)
    
    for _ in opens:
        # Check for v-show in the same line (rough)
        v_show = re.search(r'v-show="activeTab === \'(\w+)\'"', line)
        tab = v_show.group(1) if v_show else None
        stack.append((line_num, tab))
        
    for _ in closes:
        if stack:
            start_line, tab = stack.pop()
            if tab:
                results.append(f"Tab '{tab}' starts at line {start_line} and ends at line {line_num}")

for r in sorted(results, key=lambda x: int(re.search(r'starts at line (\d+)', x).group(1))):
    print(r)
