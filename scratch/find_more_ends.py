
import re

with open('resources/js/Pages/WorkOrders/Show.vue', 'r') as f:
    lines = f.readlines()

stack = []
for i, line in enumerate(lines):
    line_num = i + 1
    opens = re.findall(r'<div\b', line)
    closes = re.findall(r'</div>', line)
    
    for _ in opens:
        stack.append(line_num)
    
    for _ in closes:
        if stack:
            start = stack.pop()
            if start == 5:
                print(f"Div starting at 5 ends at line {line_num}")
            if start == 3:
                print(f"Div starting at 3 ends at line {line_num}")
