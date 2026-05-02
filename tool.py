#!/usr/bin/env python3
"""
YouTube Courses Tool
Usage: python tool.py add "Titulo" "Duracion" "Descripcion" "Dificultad" "URL" [Categoria]

Example: python tool.py add "Curso de ChatGPT" "2h 15m" "Aprende ChatGPT" "Intermedio" "https://youtube.com/watch?v=ZZq4TpNgnvg"
"""

import sys
import csv
import re
from pathlib import Path
from urllib.parse import urlparse, parse_qs

def get_video_id(url):
    """Extract YouTube video ID from URL"""
    try:
        parsed = urlparse(url)
        if parsed.hostname in ['www.youtube.com', 'youtube.com', 'm.youtube.com']:
            return parse_qs(parsed.query).get('v', [None])[0]
        elif parsed.hostname in ['youtu.be', 'www.youtu.be']:
            return parsed.path[1:]
    except Exception:
        pass
    return None

def escape_html(text):
    """Escape HTML entities"""
    return (text
        .replace('&', '&amp;')
        .replace('<', '&lt;')
        .replace('>', '&gt;')
        .replace('"', '&quot;'))

def add_course(title, duration, description, difficulty, url, category='IA'):
    """Add course to CSV and HTML"""
    base_path = Path(__file__).parent
    csv_path = base_path / 'data' / 'cursos.csv'
    html_path = base_path / 'cursos.html'
    
    video_id = get_video_id(url)
    if not video_id:
        print("Error: Invalid YouTube URL")
        return False
    
    # Add to CSV
    csv_exists = csv_path.exists()
    with open(csv_path, 'a', newline='', encoding='utf-8') as f:
        writer = csv.writer(f)
        if not csv_exists:
            writer.writerow(['titulo', 'categoria', 'duracion', 'descripcion', 'dificultad', 'url'])
        writer.writerow([title, category, duration, description, difficulty, url])
    print(f"[OK] Added to CSV: {title}")
    
    # Add to HTML
    if html_path.exists():
        html_content = html_path.read_text(encoding='utf-8')
        
        html_course = f'''
                    <!-- {escape_html(title)} -->
                    <div class="resource-card" data-category="{category.lower()}">
                        <div class="resource-preview">
                            <img src="https://img.youtube.com/vi/{video_id}/maxresdefault.jpg" alt="{escape_html(title)}">
                        </div>
                        <div class="resource-content">
                            <h3 class="resource-title">{escape_html(title)}</h3>
                            <p class="resource-description">{escape_html(description)}</p>
                            <div class="resource-meta">
                                <span class="resource-duration"><i class="far fa-clock"></i> {duration}</span>
                                <span class="resource-level"><i class="fas fa-signal"></i> {difficulty}</span>
                            </div>
                            <a href="{url}" class="resource-link" target="_blank">Ver Recurso</a>
                        </div>
                    </div>'''
        
        # Insert before closing tags
        html_content = html_content.replace(
            '</div>\n                </div>\n            </div>\n        </section>',
            html_course + '\n                </div>\n            </div>\n        </section>'
        )
        
        html_path.write_text(html_content, encoding='utf-8')
        print(f"[OK] Added to HTML: {title}")
    
    print("[OK] Course added successfully!")
    return True

def list_courses():
    """List all courses"""
    base_path = Path(__file__).parent
    csv_path = base_path / 'data' / 'cursos.csv'
    
    if not csv_path.exists():
        print("No courses found")
        return
    
    with open(csv_path, 'r', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for i, row in enumerate(reader, 1):
            print(f"{i}. {row['titulo']} | {row['categoria']} | {row['duracion']}")

def main():
    args = sys.argv[1:]
    
    if not args or args[0] in ['help', '-h', '--help']:
        print('''
YouTube Courses Tool
=================

Usage:
  python tool.py add "Titulo" "Duracion" "Descripcion" "Dificultad" "URL" [Categoria]
  python tool.py list

Examples:
  python tool.py add "Curso de ChatGPT" "2h 15m" "Aprende ChatGPT" "Intermedio" "https://youtube.com/watch?v=ZZq4TpNgnvg" "IA"
  python tool.py add "Curso de Midjourney" "1h 30m" "Genera imágenes con IA" "Principiante" "https://youtube.com/watch?v=mmfz73EW60w"
  python tool.py list

Categories: Programación, Web, Ciberseguridad, Datos, Ofimática, Redes, Idiomas, IA
        ''')
        return
    
    if args[0] in ['list', 'ls']:
        list_courses()
        return
    
    if args[0] in ['add', 'a']:
        if len(args) < 6:
            print("Usage: python tool.py add \"Titulo\" \"Duracion\" \"Descripcion\" \"Dificultad\" \"URL\" [Categoria]")
            return
        
        title = args[1]
        duration = args[2]
        description = args[3]
        difficulty = args[4]
        url = args[5]
        category = args[6] if len(args) > 6 else 'IA'
        
        add_course(title, duration, description, difficulty, url, category)
        return
    
    print("Unknown command. Run: python tool.py help")

if __name__ == '__main__':
    main()