# Material Defect Analyzer

[![CI](https://github.com/tegos/material-defect-analyzer/actions/workflows/ci.yml/badge.svg)](https://github.com/tegos/material-defect-analyzer/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-8.4-blue?logo=php)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/laravel-12.x-red?logo=laravel)](https://laravel.com/)
[![License: MIT](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A web application that finds defective regions in material surface images by running pixel-level feature extraction and distance-matrix clustering entirely in PHP, with no ML runtime required.

---

## Demo

<img src="https://raw.githubusercontent.com/tegos/material-defect-analyzer/main/assets/hero.webp" alt="Material Defect Analyzer demo" width="800">

---

## Screenshots

<img src="https://raw.githubusercontent.com/tegos/material-defect-analyzer/main/assets/demo/grid-overlay.jpg" alt="Analysis overview with grid overlay" width="800">

| | |
|---|---|
| ![Feature chart for a selected segment](https://raw.githubusercontent.com/tegos/material-defect-analyzer/main/assets/demo/segment-chart.jpg) | ![Group comparison chart](https://raw.githubusercontent.com/tegos/material-defect-analyzer/main/assets/demo/group-comparison.jpg) |
| ![Defect group highlighted in red](https://raw.githubusercontent.com/tegos/material-defect-analyzer/main/assets/demo/defect-highlight.jpg) | ![Distance matrix and group breakdown](https://raw.githubusercontent.com/tegos/material-defect-analyzer/main/assets/demo/distance-matrix.jpg) |

---

## Features

- Accepts JPEG, PNG, and GIF images with no preprocessing required
- Selects from four feature extraction algorithms per analysis session
- Configures grid resolution, intensity threshold, and group count independently
- Visualizes the full pairwise distance matrix across all segments
- Highlights defect groups automatically when variance concentration exceeds the threshold
- Renders per-segment and per-group Highcharts line chart overlays on hover
- Shows a color-coded defect percentage bar for each group
- Runs all processing server-side in PHP GD with no Python or ML runtime dependencies

---

## How It Works

The analysis pipeline runs entirely server-side in PHP using the GD image library, with no third-party ML dependencies.

**1. Upload and preprocess**

The user uploads an image and sets three parameters: grid dimensions (rows and columns), a pixel intensity threshold, and the number of output groups. The image is immediately converted to greyscale.

**2. Grid segmentation**

The greyscale image is divided into a uniform N x M grid. Each cell becomes an independent segment. The grid overlay is saved so the user can inspect exact boundaries.

**3. Feature extraction per segment**

For each segment, one of four feature vectors is computed depending on the algorithm selected:

- Intensity by rows - average pixel brightness (0-255) across all columns, one value per row.
- Intensity by columns - same computation applied column by column.
- Silhouette by rows - maximum brightness value in each row instead of average.
- Silhouette by columns - same maximum projection applied column by column.

The threshold caps any pixel value above it at 255 before these statistics are computed, letting the user suppress over-exposed areas.

**4. Pairwise distance matrix**

The algorithm computes a distance matrix across all segment pairs. Distance between two segments is the mean squared difference between their feature vectors element by element. A perfectly uniform material produces near-zero distances everywhere.

**5. Clustering into groups**

The Matrix class groups segments by iteratively finding the pair with the smallest mutual distance and assigning them to the same cluster, until all segments are distributed into the requested number of groups. Groups are sorted by total intra-group distance, so the group with the most internally dissimilar segments rises to the top.

**6. Defect scoring and highlighting**

Each segment receives a defect score derived from the variance of its feature vector: the square root of the sum of the minimum and maximum squared deviations from the segment mean. Scores are normalized to sum to 1 across all segments, then aggregated per group. Any group whose share exceeds 40% is flagged as a danger group and its segments are highlighted in red on the grid visualization.

**7. Interactive chart overlay**

The front end renders Highcharts line charts for any segment on hover, showing the raw feature vector so the user can inspect what the algorithm saw. A group comparison chart plots all segments in a selected group on the same axes for direct comparison.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Image processing | PHP GD, Intervention Image 3.x |
| Database | MySQL 8.0 |
| Charts | Highcharts (CDN) |
| Frontend | Blade templates, vanilla JS, jQuery (CDN), plain CSS |
| Reverse proxy | Nginx (Alpine) |
| Runtime | Docker Compose |

---

## Getting Started

### Requirements

- Docker and Docker Compose

### Installation

```bash
git clone https://github.com/tegos/material-defect-analyzer.git
cd material-defect-analyzer

cp .env.example .env
docker compose run --rm app composer install
docker compose up -d
docker compose exec app php artisan key:generate
```

The container entrypoint runs `storage:link` and `migrate` automatically on startup.

Open `http://localhost:8080` in your browser.

### Environment variables

The defaults in `.env.example` are pre-wired to the Docker Compose services. No changes are needed for local development.

| Variable | Default | Notes |
|---|---|---|
| `APP_URL` | `http://localhost:8080` | Must match the nginx port |
| `DB_HOST` | `mysql` | Docker service name - do not change |
| `DB_DATABASE` | `defect_analyzer` | Matches `MYSQL_DATABASE` in compose |
| `DB_USERNAME` | `defect` | Matches `MYSQL_USER` in compose |
| `DB_PASSWORD` | `secret` | Matches `MYSQL_PASSWORD` in compose |

MySQL is also exposed on port `3308` for local GUI clients (TablePlus, DBeaver). Connect with host `127.0.0.1`, port `3308`, user `defect`, password `secret`.

### Tests

```bash
docker compose exec app php artisan test
```

---

## Usage

1. On the home page, select a feature extraction algorithm and enter the grid dimensions (N columns, M rows).
2. Upload a material surface image (JPEG, PNG, or GIF). Color images are converted to greyscale automatically.
3. Set the number of output groups and, optionally, a pixel intensity threshold (0-255) to suppress over-bright regions.
4. Click Submit. The analysis page shows the image divided by the grid, the full pairwise distance matrix, per-group defect percentages, and any danger segments highlighted in red.
5. Click any segment thumbnail to load its feature vector as an interactive Highcharts line chart. Use the group comparison chart to overlay all segments within the same group on one axis.

---

## Sample Images

The repository includes three surface defect samples from the NEU-CLS dataset to try immediately:

- `assets/samples/crazing.jpg`
- `assets/samples/pitted-surface.jpg`
- `assets/samples/scratches.jpg`

Upload any of these on the home page to see a working analysis result.

---

## Project History

Built as a university diploma project in 2017. Upgraded to Laravel 12 / PHP 8.4 in 2026.

---

## Publications & Academic References

Backed by official academic research published in the proceedings of the **International Scientific Conference "Information Technology and Interactions" (IT&I-2017)**, Kyiv, November 2017.

**Citation:**
> Melnyk, R. A., & Mykhavko, I. V. (2017). *Web application for surface defects detection by classifying characteristics of its image parts*. Proceedings of the International Scientific Conference "Information Technology and Interactions" (IT&I-2017). Kyiv, 2017. P. 14.

- **Conference Program:** Article listed on page 14 of the official [IT&I-2017 Conference Program PDF](http://iti.fit.univ.kiev.ua/wp-content/uploads/%D0%9F%D1%80%D0%BE%D0%B3%D1%80%D0%B0%D0%BC%D0%B0-%D0%BA%D0%BE%D0%BD%D1%84%D0%B5%D1%80%D0%B5%D0%BD%D1%86%D1%96%D1%97-%D0%86%D0%A2%D0%86_2017.pdf).

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.
