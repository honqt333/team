import re
import sys

def check_html_tags(filepath):
    with open(filepath, 'r') as f:
        content = f.read()

    # Get only the template part
    template_start = content.find('<template>')
    template_end = content.rfind('</template>')
    if template_start == -1 or template_end == -1:
        print("Template tags not found.")
        return

    template_content = content[template_start:template_end + 11]
    
    # Simple regex to find open and close tags
    open_tags = re.findall(r'<([a-zA-Z0-9-]+)(?![^>]*/>)[^>]*>', template_content)
    close_tags = re.findall(r'</([a-zA-Z0-9-]+)>', template_content)
    
    # Remove self-closing tags and specific tags that don't need closing in this context
    self_closing = ['input', 'img', 'br', 'hr', 'meta', 'link', 'path', 'circle']
    open_tags = [t for t in open_tags if t.lower() not in self_closing and not t.startswith('!--')]
    
    print(f"Total open tags: {len(open_tags)}")
    print(f"Total close tags: {len(close_tags)}")
    
    # Count occurrences
    open_counts = {}
    for tag in open_tags:
        open_counts[tag] = open_counts.get(tag, 0) + 1
        
    close_counts = {}
    for tag in close_tags:
        close_counts[tag] = close_counts.get(tag, 0) + 1
        
    for tag in set(list(open_counts.keys()) + list(close_counts.keys())):
        o = open_counts.get(tag, 0)
        c = close_counts.get(tag, 0)
        if o != c:
            print(f"Tag mismatch: <{tag}>: open={o}, close={c}")

check_html_tags('resources/js/Pages/WorkOrders/Show.vue')
