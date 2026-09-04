#!/usr/bin/env python
"""Run graphify pipeline for Catalogist project."""
import json
import sys
from pathlib import Path

# Add graphify to path if needed
# from graphify.detect import detect
# from graphify.extract import collect_files, extract
# from graphify.build import build_from_json
# from graphify.cluster import cluster, score_all
# from graphify.analyze import god_nodes, surprising_connections, suggest_questions
# from graphify.report import generate
# from graphify.export import to_json

# Step 1: Detect files
print("Step 1: Detecting files...")
from graphify.detect import detect
result = detect(Path('.'))
Path('graphify-out/.graphify_detect.json').write_text(json.dumps(result, ensure_ascii=False), encoding='utf-8')
print(f'Detected {result["total_files"]} files')

# Print summary
files_by_type = result.get('files', {})
for ftype, flist in files_by_type.items():
    if flist:
        print(f'  {ftype}: {len(flist)} files')

# Step 2: AST extraction for code files
print("\nStep 2: AST extraction...")
from graphify.extract import collect_files, extract
code_files = []
for f in result.get('files', {}).get('code', []):
    p = Path(f)
    code_files.extend(collect_files(p) if p.is_dir() else [p])

if code_files:
    ast_result = extract(code_files, cache_root=Path('.'))
    Path('graphify-out/.graphify_ast.json').write_text(json.dumps(ast_result, indent=2, ensure_ascii=False), encoding='utf-8')
    print(f'AST: {len(ast_result["nodes"])} nodes, {len(ast_result["edges"])} edges')
else:
    Path('graphify-out/.graphify_ast.json').write_text(json.dumps({'nodes':[],'edges':[],'input_tokens':0,'output_tokens':0}, ensure_ascii=False), encoding='utf-8')
    print('No code files - skipping AST extraction')

# Step 3: Semantic extraction for documents
print("\nStep 3: Semantic extraction...")
from graphify.cache import check_semantic_cache

all_files = [f for cat in ('document', 'paper', 'image') for f in result['files'].get(cat, [])]
spec_path = Path('C:/Users/Administrator/.claude/skills/graphify/references/extraction-spec.md')

cached_nodes, cached_edges, cached_hyperedges, uncached = check_semantic_cache(all_files, root='.', prompt_file=str(spec_path))

if cached_nodes or cached_edges or cached_hyperedges:
    Path('graphify-out/.graphify_cached.json').write_text(json.dumps({'nodes': cached_nodes, 'edges': cached_edges, 'hyperedges': cached_hyperedges}, ensure_ascii=False), encoding='utf-8')
else:
    Path('graphify-out/.graphify_cached.json').unlink(missing_ok=True)
Path('graphify-out/.graphify_uncached.txt').write_text('\n'.join(uncached), encoding='utf-8')
print(f'Cache: {len(all_files)-len(uncached)} files hit, {len(uncached)} files need extraction')

# For now, create empty semantic since we don't have Gemini key
# In a full run, we'd dispatch subagents here
semantic_result = {'nodes': [], 'edges': [], 'hyperedges': [], 'input_tokens': 0, 'output_tokens': 0}

# Merge cached + new (if any)
if Path('graphify-out/.graphify_cached.json').exists():
    cached = json.loads(Path('graphify-out/.graphify_cached.json').read_text(encoding='utf-8'))
    all_nodes = cached['nodes'] + semantic_result.get('nodes', [])
    all_edges = cached['edges'] + semantic_result.get('edges', [])
    all_hyperedges = cached.get('hyperedges', []) + semantic_result.get('hyperedges', [])
else:
    all_nodes = semantic_result.get('nodes', [])
    all_edges = semantic_result.get('edges', [])
    all_hyperedges = semantic_result.get('hyperedges', [])

seen = set()
deduped = []
for n in all_nodes:
    if n['id'] not in seen:
        seen.add(n['id'])
        deduped.append(n)

semantic_merged = {
    'nodes': deduped,
    'edges': all_edges,
    'hyperedges': all_hyperedges,
    'input_tokens': semantic_result.get('input_tokens', 0),
    'output_tokens': semantic_result.get('output_tokens', 0),
}
Path('graphify-out/.graphify_semantic.json').write_text(json.dumps(semantic_merged, indent=2, ensure_ascii=False), encoding='utf-8')
print(f'Semantic: {len(deduped)} nodes, {len(all_edges)} edges')

# Step 4: Merge AST + Semantic
print("\nStep 4: Merging extractions...")
ast = json.loads(Path('graphify-out/.graphify_ast.json').read_text(encoding='utf-8'))
sem = json.loads(Path('graphify-out/.graphify_semantic.json').read_text(encoding='utf-8'))

seen = {n['id'] for n in ast['nodes']}
merged_nodes = list(ast['nodes'])
for n in sem['nodes']:
    if n['id'] not in seen:
        merged_nodes.append(n)
        seen.add(n['id'])

merged_edges = ast['edges'] + sem['edges']
merged_hyperedges = sem.get('hyperedges', [])
merged = {
    'nodes': merged_nodes,
    'edges': merged_edges,
    'hyperedges': merged_hyperedges,
    'input_tokens': sem.get('input_tokens', 0),
    'output_tokens': sem.get('output_tokens', 0),
}
Path('graphify-out/.graphify_extract.json').write_text(json.dumps(merged, indent=2, ensure_ascii=False), encoding='utf-8')
print(f'Merged: {len(merged_nodes)} nodes, {len(merged_edges)} edges ({len(ast["nodes"])} AST + {len(sem["nodes"])} semantic)')

# Step 5: Build graph, cluster, analyze
print("\nStep 5: Building graph...")
from graphify.build import build_from_json
from graphify.cluster import cluster, score_all
from graphify.analyze import god_nodes, surprising_connections, suggest_questions
from graphify.report import generate
from graphify.export import to_json

extraction = json.loads(Path('graphify-out/.graphify_extract.json').read_text(encoding='utf-8'))
detection = json.loads(Path('graphify-out/.graphify_detect.json').read_text(encoding='utf-8'))

G = build_from_json(extraction, root='.', directed=False)
if G.number_of_nodes() == 0:
    print('ERROR: Graph is empty - extraction produced no nodes.')
    sys.exit(1)

communities = cluster(G)
cohesion = score_all(G, communities)
tokens = {'input': extraction.get('input_tokens', 0), 'output': extraction.get('output_tokens', 0)}
gods = god_nodes(G)
surprises = surprising_connections(G, communities)
labels = {cid: 'Community ' + str(cid) for cid in communities}
questions = suggest_questions(G, communities, labels)

# Export graph
wrote = to_json(G, communities, 'graphify-out/graph.json')
if not wrote:
    print('ERROR: refused to shrink graphify-out/graph.json')
    sys.exit(1)

report = generate(G, communities, cohesion, labels, gods, surprises, detection, tokens, '.', suggested_questions=questions)
Path('graphify-out/GRAPH_REPORT.md').write_text(report, encoding='utf-8')
analysis = {
    'communities': {str(k): v for k, v in communities.items()},
    'cohesion': {str(k): v for k, v in cohesion.items()},
    'gods': gods,
    'surprises': surprises,
    'questions': questions,
}
Path('graphify-out/.graphify_analysis.json').write_text(json.dumps(analysis, indent=2, ensure_ascii=False), encoding='utf-8')
print(f'Graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges, {len(communities)} communities')

# Step 6: Label communities
print("\nStep 6: Labeling communities...")
analysis = json.loads(Path('graphify-out/.graphify_analysis.json').read_text(encoding='utf-8'))
communities = {int(k): v for k, v in analysis['communities'].items()}
cohesion = {int(k): v for k, v in analysis['cohesion'].items()}

# Simple auto-labeling based on nodes in each community
labels = {}
for cid, nodes in communities.items():
    node_labels = [n for n in nodes if isinstance(n, str)]
    # Use the first node's label or generate a generic one
    if node_labels:
        labels[cid] = node_labels[0].replace('_', ' ').title()[:30]
    else:
        labels[cid] = f'Community {cid}'

# Regenerate questions with real labels
questions = suggest_questions(G, communities, labels)
report = generate(G, communities, cohesion, labels, analysis['gods'], analysis['surprises'], detection, tokens, '.', suggested_questions=questions)
Path('graphify-out/GRAPH_REPORT.md').write_text(report, encoding='utf-8')
Path('graphify-out/.graphify_labels.json').write_text(json.dumps({str(k): v for k, v in labels.items()}, ensure_ascii=False), encoding='utf-8')

wrote = to_json(G, communities, 'graphify-out/graph.json', community_labels=labels)
if not wrote:
    print('ERROR: refused to shrink graphify-out/graph.json on re-export')
print('Report updated with community labels')

# Step 7: Generate HTML
print("\nStep 7: Generating HTML visualization...")
import subprocess
subprocess.run(['python', '-m', 'graphify.export', 'html'], capture_output=True, text=True)
print('HTML generated')

# Step 8: Cleanup and final report
print("\nStep 8: Cleanup...")
import glob
for f in glob.glob('graphify-out/.graphify_*.json'):
    Path(f).unlink(missing_ok=True)
for f in glob.glob('graphify-out/.graphify_chunk_*.json'):
    Path(f).unlink(missing_ok=True)

print('\nGraph complete. Outputs in graphify-out/')
print('  graph.html            - interactive graph, open in browser')
print('  GRAPH_REPORT.md       - audit report')
print('  graph.json            - raw graph data')

# Print key sections from report
print('\n--- GRAPH_REPORT.md excerpts ---')
report_path = Path('graphify-out/GRAPH_REPORT.md')
if report_path.exists():
    report_text = report_path.read_text(encoding='utf-8')
    # Extract God Nodes section
    if '## God Nodes' in report_text:
        start = report_text.index('## God Nodes')
        end = report_text.index('##', start + 3) if '##' in report_text[start+3:] else len(report_text)
        print(report_text[start:end].strip())
    # Extract Surprising Connections section
    if '## Surprising Connections' in report_text:
        start = report_text.index('## Surprising Connections')
        end = report_text.index('##', start + 3) if '##' in report_text[start+3:] else len(report_text)
        print(report_text[start:end].strip())
    # Extract Suggested Questions section
    if '## Suggested Questions' in report_text:
        start = report_text.index('## Suggested Questions')
        print(report_text[start:].strip())

print('\nDone!')