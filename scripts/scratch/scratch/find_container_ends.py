
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
            if start == 107:
                print(f"Div starting at 107 ends at line {line_num}")
            if start == 404:
                print(f"Div starting at 404 ends at line {line_num}")
            if start == 504:
                print(f"Div starting at 504 ends at line {line_num}")
            if start == 38:
                print(f"Div starting at 38 ends at line {line_num}")
            if start == 37:
                print(f"Div starting at 37 ends at line {line_num}")
