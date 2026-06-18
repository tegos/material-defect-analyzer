# Development

## Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Database | MySQL 8.0 |
| Web server | Nginx |
| Runtime | Docker Compose |

## Setup

```bash
cp .env.example .env
docker compose up -d
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

App runs at `http://localhost:8080`.

## Tests

```bash
docker compose exec app php artisan test
```

## Key directories

```
app/Http/Controllers/   ImageController (upload, analysis), HomeController
app/Image/              Core algorithm: AbstractImage, Matrix, feature subtypes
resources/views/        Blade templates
public/js/image/        Highcharts rendering (draw_graph.js)
database/migrations/    Schema (images table)
```

## Algorithm

1. Upload image, convert to greyscale
2. Divide into N x M grid; each cell = one segment
3. Extract feature vector per segment (intensity or silhouette, by rows or columns)
4. Build pairwise distance matrix (mean squared difference between vectors)
5. Cluster segments into K groups by iteratively merging closest pairs
6. Compute defect score per segment from feature vector variance
7. Flag any group whose share of total defect score exceeds 40%; highlight red
