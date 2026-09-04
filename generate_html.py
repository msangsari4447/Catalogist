#!/usr/bin/env python
"""Generate HTML visualization for graphify."""
import json
from pathlib import Path
from collections import Counter

print("Loading graph data...")
with open('graphify-out/graph.json') as f:
    graph_data = json.load(f)

print("Building networkx graph...")
from graphify.build import build_from_json
G = build_from_json(graph_data, root='.', directed=False)

# Load communities from graph.json
communities = graph_data.get('communities', {})
community_labels = graph_data.get('community_labels', {})

# If communities not in top-level, check if they're embedded in nodes
if not communities:
    print("No top-level communities found, extracting from nodes...")
    node_communities = {}
    for n in G.nodes(data=True):
        comm = n[1].get('community')
        if comm is not None:
            node_communities.setdefault(int(comm), []).append(n[0])
    communities = {int(k): v for k, v in node_communities.items()}
    community_labels = {str(k): v for k, v in graph_data.get('community_labels', {}).items()}
    print(f"Extracted {len(communities)} communities from nodes")

print(f"Graph: {G.number_of_nodes()} nodes, {G.number_of_edges()} edges")
print(f"Communities: {len(communities)}")

# Prepare member counts for better node sizing
member_counts = {cid: len(nodes) for cid, nodes in communities.items()}

output_path = Path('graphify-out/graph.html')
print(f"Generating HTML to {output_path}...")

try:
    from graphify.exporters.html import to_html
    success = to_html(
        G=G,
        communities=communities,
        output_path=str(output_path),
        community_labels=community_labels,
        member_counts=member_counts,
    )
    if success:
        print("Done! HTML generated successfully.")
    else:
        print("to_html returned False - visualization skipped")
except Exception as e:
    print(f"Error: {e}")
    import traceback
    traceback.print_exc()
