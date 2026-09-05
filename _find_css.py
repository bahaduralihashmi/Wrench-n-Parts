import os, re
fe = os.path.join(os.path.dirname(__file__), 'frontend')
refs = set()
for root, dirs, files in os.walk(fe):
    for f in files:
        if f.endswith('.html'):
            path = os.path.join(root, f)
            for line in open(path, encoding='utf-8'):
                m = re.findall(r'href="(css/[^"]+)"', line)
                for css in m:
                    refs.add(css)
print('Referenced CSS:', sorted(refs))

existing = set()
for root, dirs, files in os.walk(fe):
    for f in files:
        if f.endswith('.css'):
            rel = os.path.relpath(os.path.join(root, f), fe)
            existing.add(rel.replace('\\', '/'))
print('Existing CSS:', sorted(existing))
missing = sorted(refs - set('css/' + e for e in existing))
print('Missing CSS:', missing)
