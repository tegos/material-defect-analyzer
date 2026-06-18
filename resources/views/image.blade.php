@include('static.header')

<!-- Sidebar -->
<section id="sidebar">
    <div class="inner">
        <nav>
            <ul>
                <li><a href="/">Головна</a></li>
                <li><a href="#intro">Перегляд зображення</a></li>
            </ul>
        </nav>
    </div>
</section>

<!-- Wrapper -->
<div id="wrapper">

    <!-- Intro -->
    <section id="intro" class="wrapper style1 fullscreen">
        <div class="inner">
            <h1>Пошук дефектів</h1>
            <section>
                <h2>Зображення</h2>

                <div class="row uniform">
                    <div class="6u">
                        <span class="image fit">
                            <img src="{{$image_url}}" alt=""/>
                        </span>
                    </div>
                    <div class="6u$">
                        <span class="image fit">
                            <img src="{{$image_grid}}" alt=""/>
                        </span>
                    </div>
                </div>

                <div class="row uniform">
                    <div class="12u$">
                        <h2>{{$algorithmData['feature']}}</h2>
                        <div id="table_intensity" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
                                @forelse ($cropped_images as $cropped_image)
                                    <div class="column_graph" data-position="{{ $cropped_image['position'] }}"
                                         style="text-align:center; border: 1px solid #ddd; padding: 6px;">
                                        <img height="100" alt="{{ $cropped_image['image'] }}"
                                             src="{{ $cropped_image['image'] }}"/>
                                        <div><pre style="margin:4px 0;">{{ $cropped_image['m'] }}x{{ $cropped_image['n'] }}</pre></div>
                                        <div class="graph_intensity"
                                             id="graph_intensity_{{ $cropped_image['position'] }}"></div>
                                    </div>
                                @empty
                                @endforelse
                        </div>

                    </div>
                </div>

                <div class="row uniform">
                    <div class="12u$">
                        <h2>Матриця відстаней між графіками</h2>
                        <div class="table-wrapper">
                            <table class="table table-bordered matrix_table" id="table_matrix_distance">

                                <tbody>
                                @for ($i = 0; $i < $n*$m; $i++)
                                    <tr>
                                        @for ($j = 0; $j < $m*$n; $j++)
                                            <td class="{{($i === $j)? 'main_diagonal':''}}">
                                                <div class="content">{{ $matrix_distance[$i][$j] }}</div>
                                            </td>
                                        @endfor
                                    </tr>
                                @endfor

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <div class="row uniform">
                    <div class="12u$">
                        <h2>Виділені підгрупи</h2>
                        <div class="table-wrapper">
                            <table class="table table-bordered" id="table_groups">
                                <thead>
                                <tr>
                                    @for ($n = 0; $n < $numOfGroup; $n++)
                                        <th width="{{floor(100/$numOfGroup)}}%" class="text-center">Група {{$n+1}}</th>
                                    @endfor
                                </tr>
                                </thead>
                                <tbody>

                                @foreach($groups as $groupData)
                                    <tr class="tr_num">
                                        @foreach($groupData as $group)
                                            @if(isset($group))
                                                @php ( $image_key = $dataGraphIdentification[$group][1] .'x'. $dataGraphIdentification[$group][0])
                                                <td class="text-center">
                                                    @if(isset($cropped_images[$image_key]))
                                                        <img width="{{floor(100/$numOfGroup)}}%"
                                                             alt="{{ $cropped_images[$image_key]['image'] }}"
                                                             src="{{  $cropped_images[$image_key]['image'] }}"/>
                                                        <div class="content">
                                                            <pre>{{ $image_key }}</pre>
                                                        </div>
                                                    @endif
                                                </td>
                                            @else
                                                <td></td>
                                            @endif
                                        @endforeach
                                    </tr>
                                @endforeach

                                <tr>
                                    <td colspan="{{count($totalDistances)}}" class="text-center">Ймовірність дефекту
                                    </td>
                                </tr>

                                <tr>
                                    @foreach ($percentDataGroups as $indexKey => $percentDataGroup)
                                        <td class="text-center">
                                            <div class="stat-levels">
                                                <div class="{{$progressBarClasses[$percentDataGroup]}} stat-bar">
													<span class="stat-bar-rating"
                                                          style="width: {{$percentDataGroup}}%;">{{$percentDataGroup}}
                                                        %</span>
                                                </div>
                                            </div>
                                            <pre><b>{{ $percentDataGroup }}%</b></pre>
                                        </td>
                                    @endforeach
                                </tr>

                                <tr>
                                    <td colspan="{{count($totalDistances)}}" class="text-center">Загальна відстань у
                                        групі
                                    </td>
                                </tr>

                                <tr>
                                    @foreach ($totalDistances as $indexKey => $groupDistance)
                                        <td class="text-center">{{$groupDistance}}</td>
                                    @endforeach
                                </tr>

                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>

                <div class="row uniform">
                    <div class="12u$">
                        <h2>Графіки груп</h2>
                        <div id="groupChart" style="display:grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                            @for ($n = 0; $n < $numOfGroup; $n++)
                                <div style="text-align:center;">
                                    <div style="margin-bottom:4px; font-size:0.9em;">Група {{$n+1}}</div>
                                    @php ($image_keys = [])
                                    @for ($l = 0; $l < $maxElementInGroup; $l++)
                                        @if(isset($groups[$l][$n]))
                                            @php($group = $groups[$l][$n])
                                            @php ($image_key = $dataGraphIdentification[$group][1] .'x'. $dataGraphIdentification[$group][0])
                                            @php($image_keys[] = $image_key)
                                        @endif
                                    @endfor

                                    {{-- preserve php array building logic exactly --}}
                                    <div data-image="{{json_encode($image_keys)}}" class="groupChart"
                                         id="groupChart_{{$n}}"></div>
                                </div>
                            @endfor
                        </div>

                    </div>
                </div>

                @if($needHighlight)
                    <div class="row uniform">
                        <div class="12u$">
                            <h2>Виділення груп з дефектами</h2>
                            <div class="table-wrapper">
                                <table class="table table-bordered" id="table_highlight">
                                    <tbody>

                                    @for ($i = 0; $i < $m; $i++)
                                        <tr>
                                            @for ($j = 0; $j < $n; $j++)
                                                @php ( $image_key = $i .'x'. $j)
                                                <td class="text-center {{in_array($image_key, $dangerSegment)?'danger_segment':''}}">
                                                    @if(isset($cropped_images[$image_key]))
                                                        <img class="" alt="{{ $cropped_images[$image_key]['image'] }}"
                                                             src="{{  $cropped_images[$image_key]['image'] }}"/>
                                                    @endif
                                                </td>
                                            @endfor
                                        </tr>
                                    @endfor

                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                @endif

            </section>

        </div>

    </section>
</div>

@section('scripts')
    <script>
        let imageId = '{{$image->id}}';
        let chartTitle = '{{$algorithmData['feature']}}';
        let chartSubTitle = '{{$algorithmData['text']}}';
        let yFeatureText = '{{$algorithmData['y_feature_text']}}';

        let featureDataOfImages = '{!!$featureDataOfImages!!}';
    </script>
    <script src="/js/vendor/highcharts.js"></script>
    <script src="/js/image/draw_graph.js"></script>
    <script src="/js/image/heatmap.js"></script>
    <script src="/js/image/sidebar-nav.js"></script>
@stop

@include('static.footer')

