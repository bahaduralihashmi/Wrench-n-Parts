import urllib.request, urllib.error, json
base = 'http://localhost:8765'
r = urllib.request.urlopen(base + '/api/openapi.json')
oa = json.loads(r.read())
routes = list(oa.get('paths', {}).keys())
print('Total OpenAPI paths:', len(routes))

req = urllib.request.Request(
    base + '/api/chatbot/message',
    data=json.dumps({'message': 'brake pads', 'session_id': 't5'}).encode(),
    headers={'Content-Type': 'application/json'},
    method='POST',
)
r2 = urllib.request.urlopen(req)
d = json.loads(r2.read())
resp = d.get('data', {}).get('response', '')
print('chatbot ->', r2.status)
print('  reply:', resp[:150])
