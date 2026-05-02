#!/usr/bin/env python3
import re
import csv

with open('cursos.html', 'r', encoding='utf-8') as f:
    html = f.read()

pattern = r'<div class="resource-card" data-category="([^"]+)".*?<h3 class="resource-title">([^<]+)</h3>.*?<p class="resource-description">([^<]+)</p>.*?<span class="resource-duration"><i[^>]*>([^<]+)</span>.*?<span class="resource-level"><i[^>]*>([^<]+)</span>.*?<a href="([^"]+)"'

courses = []
for match in re.finditer(pattern, html, re.DOTALL):
    courses.append([
        match.group(2).strip(),  # title
        match.group(1).strip(),  # category
        match.group(4).strip(),  # duration
        match.group(3).strip(),  # description
        match.group(5).strip(),  # level
        match.group(6).strip()   # url
    ])

with open('data/cursos.csv', 'w', newline='', encoding='utf-8') as f:
    writer = csv.writer(f)
    writer.writerow(['titulo', 'categoria', 'duracion', 'descripcion', 'dificultad', 'url'])
    writer.writerows(courses)

print(f'Updated {len(courses)} courses')