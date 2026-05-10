
import re

def analyze_full_depth(file_path):
    with open(file_path, 'r') as f:
        lines = f.readlines()
    
    depth = 0
    for i, line in enumerate(lines):
        line_num = i + 1
        opens = len(re.findall(r'<div\b', line))
        closes = len(re.findall(r'</div>', line))
        
        for _ in range(opens):
            depth += 1
            if line_num >= 500 and line_num <= 510:
                print(f"Line {line_num}: OPEN div. New depth: {depth}")
                
        for _ in range(closes):
            if line_num >= 500 and line_num <= 510:
                print(f"Line {line_num}: CLOSE div. New depth: {depth - 1}")
            depth -= 1

analyze_full_depth('resources/js/Pages/WorkOrders/Show.vue')
