
import re

def count_tags(file_path):
    with open(file_path, 'r') as f:
        content = f.read()
    
    # Remove script and style sections
    template_match = re.search(r'<template>(.*)</template>', content, re.DOTALL)
    if not template_match:
        return "No template found"
    
    template_content = template_match.group(1)
    
    lines = template_content.split('\n')
    stack = []
    
    for i, line in enumerate(lines):
        # Very simple tag detection
        opens = re.findall(r'<div\b', line)
        closes = re.findall(r'</div>', line)
        
        for _ in opens:
            stack.append(i + 1)
        for _ in closes:
            if stack:
                stack.pop()
            else:
                print(f"Extra closing div at line {i + 1}")
    
    if stack:
        print(f"Unclosed divs starting at lines: {stack}")
    else:
        print("All divs are balanced in the template section.")

count_tags('resources/js/Pages/WorkOrders/Show.vue')
