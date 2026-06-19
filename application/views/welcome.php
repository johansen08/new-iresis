<!-- START WIDGETS -->
<div class="row">
    <div class="col-md-3">

        <!-- START WIDGET SLIDER -->
        <div class="widget widget-default widget-carousel">
            <div class="owl-carousel" id="owl-example">
                <?php foreach ($header_daily_report as $key => $value): ?>
                    <div>
                        <div class="widget-title"><?= str_replace('_', ' ', $key) ?></div>
                        <div class="widget-subtitle">Hari ini</div>
                        <div class="widget-int"><?= $value ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- END WIDGET SLIDER -->

    </div>
    <div class="col-md-3">

        <!-- START WIDGET MESSAGES -->
        <div class="widget widget-default widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-envelope"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count">48</div>
                <div class="widget-title">New messages</div>
                <div class="widget-subtitle">In your mailbox</div>
            </div>
        </div>
        <!-- END WIDGET MESSAGES -->

    </div>
    <div class="col-md-3">

        <!-- START WIDGET REGISTRED -->
        <div class="widget widget-default widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-user"></span>
            </div>
            <div class="widget-data">
                <div class="widget-int num-count">375</div>
                <div class="widget-title">Registred users</div>
                <div class="widget-subtitle">On your website</div>
            </div>
        </div>
        <!-- END WIDGET REGISTRED -->

    </div>
    <div class="col-md-3">

        <!-- START WIDGET CLOCK -->
        <div class="widget widget-info widget-padding-sm">
            <div class="widget-big-int plugin-clock">00:00</div>
            <div class="widget-subtitle plugin-date">Loading...</div>
        </div>
        <!-- END WIDGET CLOCK -->

    </div>
</div>
<!-- END WIDGETS -->

<div class="row">
    <div class="col-md-12">
        <h3 class="panel-title" style="margin: 20px 0 10px 0; font-weight: bold; color: #555;">Operational Metrics</h3>
    </div>
</div>

<div class="row">
    <!-- Box 1: Courier Totals Carousel -->
    <div class="col-md-3">
        <div class="widget widget-info widget-carousel">
            <div class="owl-carousel" id="carousel-courier-total">
                <?php if(!empty($courier_totals)): ?>
                    <?php foreach($courier_totals as $ct): ?>
                    <div>                                    
                        <div class="widget-title">Total Handover Today</div>                                    
                        <div class="widget-subtitle"><?= $ct['nama_kurir'] ?></div>
                        <div class="widget-int"><?= number_format($ct['total']) ?></div>
                        <div class="widget-subtitle">Resi</div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>                                    
                        <div class="widget-title">Total Handover Today</div>                                    
                        <div class="widget-subtitle">Belum ada data</div>
                        <div class="widget-int">0</div>
                    </div>
                <?php endif; ?>
            </div>                            
        </div>
    </div>

    <!-- Box 2: Deadlines Carousel -->
    <div class="col-md-3">
        <div class="widget widget-primary widget-carousel">
            <div class="owl-carousel" id="carousel-deadlines">
                <?php foreach($deadlines_breakdown as $db): ?>
                <div>                                    
                    <div class="widget-title">Batas Kirim</div>                                    
                    <div class="widget-subtitle"><?= date('d M Y', strtotime($db['date'])) ?></div>
                    <div class="widget-int"><?= number_format($db['total']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>                            
        </div>
    </div>

    <!-- Box 3: Production Target Today (Simplified, No Carousel) -->
    <div class="col-md-6">
        <div class="widget widget-success widget-item-icon">
            <div class="widget-item-left">
                <span class="fa fa-bullseye"></span>
            </div>
            <div class="widget-data">
                <div class="widget-title" style="font-weight: bold;">Target Produksi Harian (Batas Kirim Hari Ini)</div>
                <div class="row" style="margin-top: 10px;">
                    <div class="col-md-3">
                        <div class="widget-subtitle">Total</div>
                        <div style="font-size: 18px; font-weight: bold; color: #ffffff;"><?= number_format($production_today['total']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="widget-subtitle">Picker</div>
                        <div style="font-size: 18px; font-weight: bold; color: #ffffff;"><?= number_format($production_today['picked']) ?> / <?= number_format($production_today['total']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="widget-subtitle">Packer</div>
                        <div style="font-size: 18px; font-weight: bold; color: #ffffff;"><?= number_format($production_today['packed']) ?> / <?= number_format($production_today['total']) ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="widget-subtitle">HO</div>
                        <div style="font-size: 18px; font-weight: bold; color: #ffffff;"><?= number_format($production_today['ho']) ?> / <?= number_format($production_today['total']) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        if($("#carousel-courier-total").length > 0){
            $("#carousel-courier-total").owlCarousel({
                navigation : false, 
                slideSpeed : 300, 
                paginationSpeed : 400, 
                singleItem : true,
                autoPlay: 4000
            });
        }
        if($("#carousel-deadlines").length > 0){
            $("#carousel-deadlines").owlCarousel({
                navigation : false, 
                slideSpeed : 300, 
                paginationSpeed : 400, 
                singleItem : true,
                autoPlay: 5000
            });
        }
    });
</script>