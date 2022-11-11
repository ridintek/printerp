<?php
	$year = date('Y', strtotime($end_date));
	$month = date('m', strtotime($end_date));;
	$date = date('d', strtotime($end_date));;
	$hour = date('H', strtotime($end_date));;
	$minute = date('i', strtotime($end_date));;
	$second = date('s', strtotime($end_date));;

	if (now() >= strtotime("{$year}-{$month}-{$date} {$hour}:{$minute}:{$second}")) {
		// redirect('/admin');
	}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<title>PrintERP Maintenance</title>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
<!--===============================================================================================-->
	<link rel="icon" type="image/png" href="<?= base_url(); ?>images/icons/favicon.ico"/>
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/vendor/bootstrap/css/bootstrap.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/fonts/font-awesome-4.7.0/css/font-awesome.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/vendor/animate/animate.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/vendor/select2/select2.min.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/vendor/countdowntime/flipclock.css">
<!--===============================================================================================-->
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/css/util.css">
	<link rel="stylesheet" type="text/css" href="<?= base_url(); ?>themes/maintenance/css/main.css">
<!--===============================================================================================-->
  <style>
    /** Overriding Themes */
    .l1-txt1 {
      font-size: 30px;
      font-weight: bold;
    }
  </style>
</head>
<body>


	<div class="bg-img1 size1 overlay1 p-b-35 p-l-15 p-r-15" style="background-image: url('<?= base_url(); ?>themes/maintenance/images/bg01.jpg');">
		<div class="flex-col-c p-b-50 respon1">
			<div class="wrappic1">
				<a href="#">
				</a>
			</div>

			<h3 class="l1-txt1 txt-center p-t-30 p-b-20">
				Maaf atas ketidaknyamanannya.<br><br>PrintERP sedang maintenance.<br><br>
				Dan akan selesai dalam waktu:
			</h3>

			<div class="cd100"></div>

		</div>

		<!--  -->
		<!--
		<div class="flex-w flex-c-m p-b-35">
			<a href="#" class="size3 flex-c-m how-social trans-04 m-r-3 m-l-3 m-b-5">
				<i class="fa fa-facebook"></i>
			</a>

			<a href="#" class="size3 flex-c-m how-social trans-04 m-r-3 m-l-3 m-b-5">
				<i class="fa fa-twitter"></i>
			</a>

			<a href="#" class="size3 flex-c-m how-social trans-04 m-r-3 m-l-3 m-b-5">
				<i class="fa fa-youtube-play"></i>
			</a>
		</div>-->
	</div>





<!--===============================================================================================-->
	<script src="<?= base_url(); ?>themes/maintenance/vendor/jquery/jquery-3.2.1.min.js"></script>
<!--===============================================================================================-->
	<script src="<?= base_url(); ?>themes/maintenance/vendor/bootstrap/js/popper.js"></script>
	<script src="<?= base_url(); ?>themes/maintenance/vendor/bootstrap/js/bootstrap.min.js"></script>
<!--===============================================================================================-->
	<script src="<?= base_url(); ?>themes/maintenance/vendor/select2/select2.min.js"></script>
<!--===============================================================================================-->
	<script src="<?= base_url(); ?>themes/maintenance/vendor/countdowntime/flipclock.min.js"></script>
	<script src="<?= base_url(); ?>themes/maintenance/vendor/countdowntime/moment.min.js"></script>
	<script src="<?= base_url(); ?>themes/maintenance/vendor/countdowntime/moment-timezone.min.js"></script>
	<script src="<?= base_url(); ?>themes/maintenance/vendor/countdowntime/moment-timezone-with-data.min.js"></script>
	<script src="<?= base_url(); ?>themes/maintenance/vendor/countdowntime/countdowntime.js"></script>
	<script>
		$('.cd100').countdown100({
			/*Set Endtime here*/
			/*Endtime must be > current time*/
			endtimeYear: <?= $year; ?>,
			endtimeMonth: <?= $month; ?>,
			endtimeDate: <?= $date; ?>,
			endtimeHours: <?= $hour; ?>,
			endtimeMinutes: <?= $minute; ?>,
			endtimeSeconds: <?= $second; ?>,
			timeZone: ""
			// ex:  timeZone: "America/New_York"
			//go to " http://momentjs.com/timezone/ " to get timezone
		});


	</script>
<!--===============================================================================================-->
	<script src="<?= base_url(); ?>themes/maintenance/vendor/tilt/tilt.jquery.min.js"></script>
	<script >
		$('.js-tilt').tilt({
			scale: 1.1
		})
	</script>
<!--===============================================================================================-->
	<script src="<?= base_url(); ?>themes/maintenance/js/main.js"></script>

</body>
</html>
