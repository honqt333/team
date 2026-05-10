
import re

def analyze_tabs(file_path):
    with open(file_path, 'r') as f:
        lines = f.readlines()
    
    in_tab_container = False
    current_tab = None
    stack = []
    
    for i, line in enumerate(lines):
        line_num = i + 1
        
        # Detect Tab Content Container
        if '<div class="p-6">' in line and line_num > 400:
            in_tab_container = True
            stack.append(('p-6', line_num))
            print(f"Tab Container started at line {line_num}")
            continue
            
        if not in_tab_container:
            continue
            
        # Detect Tab starts
        tab_match = re.search(r'(v-show|v-if)="activeTab === \'(\w+)\'?"', line)
        if tab_match:
            tab_name = tab_match.group(2)
            current_tab = tab_name
            stack.append((tab_name, line_num))
            print(f"  Tab '{tab_name}' started at line {line_num}")
            continue
            
        # Simple div counting
        opens = len(re.findall(r'<div\b', line))
        closes = len(re.findall(r'</div>', line))
        
        for _ in range(opens):
            stack.append(('div', line_num))
        
        for _ in range(closes):
            if stack:
                tag, start_line = stack.pop()
                if tag == 'p-6':
                    in_tab_container = False
                    print(f"Tab Container ended at line {line_num}")
                elif tag != 'div':
                    print(f"  Tab '{tag}' ended at line {line_num}")
            else:
                print(f"Extra closing div at line {line_num}")

analyze_tabs('resources/js/Pages/WorkOrders/Show.vue')
