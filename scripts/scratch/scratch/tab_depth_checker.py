
import re

def analyze_tab_depth(file_path):
    with open(file_path, 'r') as f:
        lines = f.readlines()
    
    depth = 0
    for i, line in enumerate(lines):
        line_num = i + 1
        opens = len(re.findall(r'<div\b', line))
        closes = len(re.findall(r'</div>', line))
        
        tab_match = re.search(r'(v-show|v-if)="activeTab === \'(\w+)\'?"', line)
        if tab_match:
            print(f"Tab '{tab_match.group(2)}' at line {line_num} is at depth {depth}")
            
        depth += opens
        depth -= closes

analyze_tab_depth('resources/js/Pages/WorkOrders/Show.vue')
