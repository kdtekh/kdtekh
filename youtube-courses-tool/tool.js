#!/usr/bin/env node

const https = require('https');
const fs = require('fs');
const path = require('path');
const { URL } = require('url');

function getYouTubeVideoId(url) {
    try {
        const urlObj = new URL(url);
        if (urlObj.hostname.includes('youtube.com') || urlObj.hostname.includes('youtu.be')) {
            return urlObj.searchParams.get('v') || urlObj.pathname.slice(1);
        }
    } catch (e) {}
    return null;
}

function extractMetadata(html) {
    const result = {
        title: '',
        duration: '',
        description: ''
    };

    const titleMatch = html.match(/<title>([^<]+)<\/title>/i);
    if (titleMatch) {
        result.title = titleMatch[1].replace(' - YouTube', '').trim();
    }

    const durationMatch = html.match(/lengthSeconds":"(\d+)"/);
    if (durationMatch) {
        const seconds = parseInt(durationMatch[1]);
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        
        if (hours > 0) {
            result.duration = `${hours}h ${minutes}m`;
        } else if (minutes > 0) {
            result.duration = `${minutes}m ${secs}s`;
        } else {
            result.duration = `${secs}s`;
        }
    }

    const shortDescMatch = html.match(/"shortDescription":"([^"]+)"/);
    if (shortDescMatch) {
        result.description = shortDescMatch[1].substring(0, 200);
    }

    return result;
}

function fetchYouTubeVideo(url) {
    return new Promise((resolve, reject) => {
        const urlObj = new URL(url);
        const videoId = getYouTubeVideoId(url);
        
        if (!videoId) {
            reject(new Error('Invalid YouTube URL'));
            return;
        }

        const options = {
            hostname: 'www.youtube.com',
            path: `/watch?v=${videoId}`,
            method: 'GET',
            headers: {
                'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            }
        };

        const req = https.request(options, (res) => {
            let data = '';
            res.on('data', (chunk) => data += chunk);
            res.on('end', () => {
                resolve(extractMetadata(data));
            });
        });

        req.on('error', reject);
        req.end();
    });
}

function addCourseToCSV(course) {
    const csvPath = path.join(__dirname, 'data', 'cursos.csv');
    const fileContent = fs.readFileSync(csvPath, 'utf-8');
    const rows = fileContent.split('\n');
    
    const newRow = `"${course.title}","IA","${course.duration}","${course.description}","Intermedio","${course.url}"`;
    
    const header = rows[0];
    rows.splice(1, 0, newRow);
    
    fs.writeFileSync(csvPath, rows.join('\n'), 'utf-8');
    console.log('Course added to CSV:', course.title);
}

function addCourseToHTML(course) {
    const htmlPath = path.join(__dirname, 'cursos.html');
    let content = fs.readFileSync(htmlPath, 'utf-8');
    
    const courseTemplate = `
                    <!-- Nuevo Curso -->
                    <div class="resource-card" data-category="ia">
                        <div class="resource-preview">
                            <img src="https://img.youtube.com/vi/${getYouTubeVideoId(course.url)}/maxresdefault.jpg" alt="${course.title}">
                        </div>
                        <div class="resource-content">
                            <h3 class="resource-title">${course.title}</h3>
                            <p class="resource-description">${course.description}</p>
                            <div class="resource-meta">
                                <span class="resource-duration"><i class="far fa-clock"></i> ${course.duration}</span>
                                <span class="resource-level"><i class="fas fa-signal"></i> Intermedio</span>
                            </div>
                            <a href="${course.url}" class="resource-link" target="_blank">Ver Recurso</a>
                        </div>
                    </div>`;
    
    content = content.replace('</div>\n                </div>\n            </div>\n        </section>', courseTemplate + '\n                </div>\n            </div>\n        </section>');
    
    fs.writeFileSync(htmlPath, content, 'utf-8');
    console.log('Course added to HTML:', course.title);
}

async function main() {
    const args = process.argv.slice(2);
    const command = args[0];
    
    if (command === 'add') {
        const url = args[1];
        if (!url) {
            console.error('Usage: node tool.js add <youtube-url>');
            process.exit(1);
        }
        
        console.log('Fetching metadata from:', url);
        const metadata = await fetchYouTubeVideo(url);
        
        const course = {
            title: metadata.title,
            duration: metadata.duration,
            description: metadata.description,
            url: url
        };
        
        console.log('\n--- Course Info ---');
        console.log('Title:', course.title);
        console.log('Duration:', course.duration);
        console.log('Description:', course.description);
        
        addCourseToCSV(course);
        addCourseToHTML(course);
        
    } else if (command === 'help') {
        console.log(`
YouTube Courses Tool
=============

Usage:
  node tool.js add <youtube-url>   Add a new course from YouTube URL
  node tool.js help            Show this help message

Examples:
  node tool.js add https://www.youtube.com/watch?v=ZZq4TpNgnvg
        `);
    } else {
        console.log('Unknown command. Run: node tool.js help');
    }
}

main().catch(console.error);