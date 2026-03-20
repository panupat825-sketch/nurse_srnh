<!DOCTYPE.php>
<html lang="en">

 <head>
        <meta charset="utf-8">
        <title>กลุ่มการพยาบาล โรงพยาบาลศรีรัตนะ</title>
        <meta content="width=device-width, initial-scale=1.0" name="viewport">
        <meta content="" name="keywords">
        <meta content="" name="description">

        <!-- Google Web Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Playball&display=swap" rel="stylesheet">

        <!-- Icon Font Stylesheet -->
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
        <link href="skho_TBm_icon.icon" rel="shortcut icon" type="image/x-icon" />
        
        <!-- Libraries Stylesheet -->
        <link href="lib/animate/animate.min.css" rel="stylesheet">
        <link href="lib/lightbox/css/lightbox.min.css" rel="stylesheet">
        <link href="lib/owlcarousel/owl.carousel.min.css" rel="stylesheet">

        <!-- Customized Bootstrap Stylesheet -->
        <link href="css/bootstrap.min.css" rel="stylesheet">

        <!-- Template Stylesheet -->
        <link href="css/style.css" rel="stylesheet">
    </head>

    <body>

        <!-- Spinner Start -->
        <div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
            <div class="spinner-grow text-primary" role="status"></div>
        </div>
        <!-- Spinner End -->


        <!-- Navbar start -->
        <div class="container-fluid nav-bar">
            <div class="container">
                <nav class="navbar navbar-light navbar-expand-lg py-4">
                    <a href="index.php" class="navbar-brand">
                        <h4 class="text-primary fw-bold mb-0">Nurse<span class="text-dark">SRNH</span> </h4>
                    </a>
                    <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                        <span class="fa fa-bars text-primary"></span>
                    </button>
                    <div class="collapse navbar-collapse" id="navbarCollapse">
                        <div class="navbar-nav mx-auto">
                            <a href="index.php" class="nav-item nav-link active">หน้าแรก</a>
							<div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">วิสัยทัศน์-พันธกิจ</a> 
                                <div class="dropdown-menu bg-light">
									<a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vRBwg_rQHtk7XOIprRigo2GbdvzEgkhC-mXIhfroyA3hz0jR1im1xRjzoxmTWDHpA/pubhtml" target="_blank" class="dropdown-item">วิสัยทัศน์และประเด็นยุทธศาสตรองค์กรพยาบาล</a>
                                    <a href="plan.php" target="_blank" class="dropdown-item">แผนกลยุทธ์/โครงการ</a>
                                    <a href="policy.php" target="_blank" class="dropdown-item">นโยบาย</a>
                                </div>
                            </div>
                            <a href="org_ch.php" class="nav-item nav-link">ทำเนียบ</a>
							<a href="/nurse_srnh/pdf/Self-assessment.pdf" target="_blank" class="nav-item nav-link">ประเมินตนเอง</a>
							<div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">ITA</a>
                                <div class="dropdown-menu bg-light">
                                    <a href="http://sirattanahosp.moph.go.th/ita/ita64/itastr.php#home" target="_blank" class="dropdown-item">ITA64</a>
                                    <a href="http://sirattanahosp.moph.go.th/ita/ita65/itastr.php#home" target="_blank" class="dropdown-item">ITA65</a>
                                    <a href="http://sirattanahosp.moph.go.th/ita/ita66/itastr.php#home" target="_blank" class="dropdown-item">ITA66</a>
									<a href="http://sirattanahosp.moph.go.th/ita/ita67/itastr.php#home" target="_blank" class="dropdown-item">ITA67</a>
                                </div>
                            </div>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">DATA</a>
                                <div class="dropdown-menu bg-light">
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vT5TbE7cNVSoYr2njwTENFd_RIguXgBQKJxyeqkjzv7YYW8nTwv1Vf9v2-4-S_wbBKImSpckCHDGxmM/pubhtml"  target="_blank" class="dropdown-item">ข้อมูลพื้นฐานโรงพยาบาลศรีรัตนะ</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vT0n0DCpvPQPIzyOIiq0e9TGbXWoJZZ1ZrXEBV6GsCbqxFKVwjvVGkbdUNG1LydgFqlK1zE76JVhaSS/pubhtml"  target="_blank" class="dropdown-item">สถิติผู้มารับบริการ อัตราครองเตียง CMI ปีงบประมาณปัจจุบัน</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vRuOpP00ZIJ2btq8nxpfn2D2-Xc2IkncqdZjgfXFOY07JnSgsKoamtiVy58Zqt1etN7v7W5hhOwnC5V/pubhtml"  target="_blank" class="dropdown-item">ข้อมูลประชากรอำเภอศรีรัตนะ (ข้อมูลจาก HDC)</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vQCNKO5TRMU-tJswxtjvC8V8rpgjl-0ztu6rx_WsU5tD11EveWPxH3GtvdWxBUVZA/pubhtml"  target="_blank" class="dropdown-item">รายงานวันนอน CMI แยกตามตึกผู้ป่วย</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vTyLG_A0T7Bve09WVcsW3ZP_xsW-F4mAFf6ixKw8h0Lrm6B3ifMFKmJRUUaFr9Jyg/pubhtml"  target="_blank" class="dropdown-item">รายงาน 10 อันดับโรค (OPD/IPD/Refer/Re-admit)</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vREiIy7J9zo1hYCN6QIBFzO6qPV5KPoFi_Fehf3peUatyn_DWlR7Rs3ddKZsdnjSKR41pidqzQcWlaF/pubhtml"  target="_blank" class="dropdown-item">รายงานสำคัญโรงพยาบาลศรีรัตนะ</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vT7HPn6uoWtahOntBGwLWyVp8wXxrbTsIy-y14b75dwE4o2w33vYMOuwO5Io7DwmTLF27KrADFLFA5b/pubhtml"  target="_blank" class="dropdown-item">รายงานสำคัญ IPD</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vQahvcKzxM62JPt_lKB91mZLlII1Ml-R_DGZ5s5woTtseR031yVYZ18cvSNYqWX5ZgvbUh3DOhuY-IB/pubhtml"  target="_blank" class="dropdown-item">รายงาน 10 อันดับโรค IPD แยกตามตึกผู้ป่วย</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vTEhzALt3ilQmjR6HFG8m597AEsMGAl8b4w8LxAMGTB_eKMBRgQ3ixO4P8W3dLfI8bJshAzk8Xb4iLm/pubhtml"  target="_blank" class="dropdown-item">รายงานสำคัญ OPD</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vRlQ-oA4pTzqPvgm0jeDSxbdf2bJbKL3qpxSCuCBEg8sqwTQb4OkKjqIPKuCdLJ9Lw5n8Xoc45dACKU/pubhtml"  target="_blank" class="dropdown-item">จำนวนผู้ป่วย OPD แยกตามจุกซักประวัติ</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vT3rrA5mfgCYVsO_MWBziLImXGpca_jeyHuIa7OOTJkMGO2QztAmgnDNyPzV0h9g6FEanCKlsgywpRM/pubhtml"  target="_blank" class="dropdown-item">จำนวนผู้ป่วย OPD/IPD แยกตามสิทธิการรักษา</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vRlyiXIJ64SQ3Ijn7t57iMFi99LG5SZXikUYLK-dn9syuMylYWDfcQb-QymGLfkwp2JBvUvlFkwimR8/pubhtml"  target="_blank" class="dropdown-item">รายงานรายได้คลินิกเฉพาะทาง</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vSKlDwU-CfZgPlFlAfFl55-dISq9_ndyb0gyFVC4MaRjanu9nuv2u5S2_Hy4oqwc-zMr4oI2AydBq0_/pubhtml"  target="_blank" class="dropdown-item">รายงานรายได้ทันตกรรม</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vRj6IJ7hVxAH3wdog9C3c6WwYNNpt_9sGVMb-nnMX-vBNayaTHxh_XSut8O-6qffl9I81cH0S-drHFF/pubhtml"  target="_blank" class="dropdown-item">รายงานรายได้แต่ละเดือน</a>
                                    <a href="https://docs.google.com/spreadsheets/d/e/2PACX-1vQIJUXYY5mFFltCoKnUb4mP8OIc6lXQfS-KBuQhukGDlRsTu9t1AUtZdw27IDzX0MuAjDIkVpUexzvR/pubhtml"  target="_blank" class="dropdown-item">รายงานอื่นๆ</a>
                                    <a href="http://www.sirattanahospital.go.th/pdfFile/form_report/form_report.pdf"  target="_blank" class="dropdown-item">แบบฟอร์มขอข้อมูล/รายงาน โรงพยาบาลศรีรัตนะ</a>
                                    <a href="http://www.sirattanahospital.go.th/pdfFile/form_report/form_report1.pdf"  target="_blank" class="dropdown-item">แบบฟอร์มขอสำเนาเวชระเบียนอิเล็กทรอนกิส์ โรงพยาบาลศรีรัตนะ</a>
                                </div>
                            </div>
                            <div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">Service</a>
                                <div class="dropdown-menu bg-light">
									<a href="https://www.don.go.th/nperson/app/index.php/member/login" class="dropdown-item">THAILAND NURSING DIGITAL PLATFORM</a>
                                    <a href="https://docs.google.com/forms/d/e/1FAIpQLScaK8EOMz6MiRxsBmyGnc3ngzchqEBysjVbFLwukf_J5_i1vA/viewform" class="dropdown-item">ส่งใบเบิกยา รพ.สต.</a>
                                    <a href="http://www.sirattanahospital.go.th/booking-master/" class="dropdown-item">ระบบจองห้องประชุม</a>
                                    <a href="http://www.sirattanahospital.go.th/eoffice-master/" class="dropdown-item">ระบบแจ้งซ่อมคอมพิวเตอร์</a>
                                    <a href="http://www.sirattanahospital.go.th/carbooking-master/" class="dropdown-item">ระบบจองรถ โรงพยาบาลศรีรัตนะ</a>
                                    <a href="https://srn.thai-nrls.org/" class="dropdown-item">ระบบสารสนเทศการบริหารจัดการความเสี่ยงของสถานพยาบาล</a>
                                </div>
                            </div>
							<div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">รายงาน</a>
                                <div class="dropdown-menu bg-light">
                                    <a href="https://docs.google.com/spreadsheets/d/13KUSQBR9gqxwXlZgxVTsgYbqhl6btD6BPTstQzzDGSE/edit#gid=1095273588" target="_blank" class="dropdown-item">รายงานตัวชี้วัด QA</a>
                                    <a href="https://docs.google.com/spreadsheets/d/1fX2LmLZVnsqCfWhfcOXV9Jp4O_B6Cp2m/edit#gid=1723458307" target="_blank" class="dropdown-item">รายงานผู้รับบริการฝ่ายการพยาบาลปีงบประมาณ 2567</a>
									<a href="https://docs.google.com/spreadsheets/d/1D39Uw9i0MjvUbhaIrjVzx7y0zJtNPDvR/edit#gid=1668691192" target="_blank" class="dropdown-item">รายงานแผนกลยุทธ์ องค์กรพยาบาลโรงพยาบาลศรีรัตนะ 2567</a>
								</div>
                            </div>
                            <a href="contact.php" class="nav-item nav-link">Contact</a>
							<div class="nav-item dropdown">
                                <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">เพิ่มเติม</a>
                                <div class="dropdown-menu bg-light">
                                    <a href="#" target="_blank" class="dropdown-item">PCT</a>
                                    <a href="#" target="_blank" class="dropdown-item">PTC</a>
									<a href="#" target="_blank" class="dropdown-item">IC</a>
									<a href="#" target="_blank" class="dropdown-item">RM</a>
									<a href="#" target="_blank" class="dropdown-item">IT</a>
									<a href="#" target="_blank" class="dropdown-item">ENV</a>
									<a href="#" target="_blank" class="dropdown-item">อื่นๆ</a>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </nav>
            </div>
        </div>
        <!-- Navbar End -->


        <!-- Modal Search Start -->
        <div class="modal fade" id="searchModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content rounded-0">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">Search by keyword</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body d-flex align-items-center">
                        <div class="input-group w-75 mx-auto d-flex">
                            <input type="search" class="form-control bg-transparent p-3" placeholder="keywords" aria-describedby="search-icon-1">
                            <span id="search-icon-1" class="input-group-text p-3"><i class="fa fa-search"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal Search End -->


        <!-- Hero Start -->
        <div class="container-fluid bg-light py-6 my-6 mt-0">
            <div class="container text-center animated bounceInDown">
                <h1 class="display-1 mb-4">Contact</h1>
                <ol class="breadcrumb justify-content-center mb-0 animated bounceInDown">
                    <li class="breadcrumb-item"><a href="#">Home</a></li>
                    <li class="breadcrumb-item"><a href="#">Pages</a></li>
                    <li class="breadcrumb-item text-dark" aria-current="page">Contact</li>
                </ol>
            </div>
        </div>
        <!-- Hero End -->


         <!-- Footer Start -->
         <div class="container-fluid footer py-6 my-6 mb-0 bg-light wow bounceInUp" data-wow-delay="0.1s">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-item">
                            <h3 class="text-primary">Sirattana<span class="text-dark">Hosppital</span></h3>
                            <p class="lh-lg mb-4"></p>
                            <div class="footer-icon d-flex">
                                <a class="btn btn-primary btn-sm-square me-2 rounded-circle" href="https://www.facebook.com/sirattanahosp/" target="_blank"><i class="fab fa-facebook-f"></i></a>
                                <a class="btn btn-primary btn-sm-square me-2 rounded-circle" href=""><i class="fab fa-twitter"></i></a>
                                <a href="#" class="btn btn-primary btn-sm-square me-2 rounded-circle"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="btn btn-primary btn-sm-square rounded-circle"><i class="fab fa-linkedin-in"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-item">
                            <h5 class="mb-4">เว็บที่เกี่ยวข้อง</h5>
                            <div class="d-flex flex-column align-items-start">
                                <a class="text-body mb-2" href="http://www.ssko.moph.go.th/"><i class="fa fa-check text-primary me-2"></i>สำนักงานสาธารณสุขจังหวัดศรีสะเกษ</a>
                                <a class="text-body mb-3" href="http://www.gprocurement.go.th/new_index.html"><i class="fa fa-check text-primary me-2"></i>ระบบการจัดซื้อจัดจ้างภาครัฐ</a>
                                <a class="text-body mb-3" href="http://www.ssko.moph.go.th/kpi.php"><i class="fa fa-check text-primary me-2"></i>ระบบติดตามตัวชี้วัด</a>
                                <a class="text-body mb-3" href="https://ssk.hdc.moph.go.th/hdc/main/index_pk.php"><i class="fa fa-check text-primary me-2"></i>ระบบ HDC</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-item">
                            <h4 class="mb-4">Contact Us</h4>
                            <div class="d-flex flex-column align-items-start">
                                <p><i class="fa fa-map-marker-alt text-primary me-2"></i> 182 M.15 Sikeaw Sirattana Sisaket </p>
                                <p><i class="fa fa-phone-alt text-primary me-2"></i> 045677014</p>
                                <p><i class="fas fa-envelope text-primary me-2"></i> srn10939@gmail.com</p>
                                <p><i class="fa fa-clock text-primary me-2"></i> 24 Hours Service</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Footer End -->


        <!-- Copyright Start -->
        <div class="container-fluid copyright bg-dark py-4">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
                        <span class="text-light"><a href="#"><i class="fas fa-copyright text-light me-2"></i>www.sirattanahospital.go.th</a>, All right reserved.</span>
                    </div>
                    <div class="col-md-6 my-auto text-center text-md-end text-white">
                        <!--/*** This template is free as long as you keep the below author’s credit link/attribution link/backlink. ***/-->
                        <!--/*** If you'd like to use the template without the below author’s credit link/attribution link/backlink, ***/-->
                        <!--/*** you can purchase the Credit Removal License from "https://htmlcodex.com/credit-removal". ***/-->
                        Designed By <a class="border-bottom" href="https://htmlcodex.com">HTML Codex</a> Distributed By <a class="border-bottom" href="https://themewagon.com">ThemeWagon</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- Copyright End -->


        <!-- Back to Top -->
        <a href="#" class="btn btn-md-square btn-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>   

        
    <!-- JavaScript Libraries -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="lib/wow/wow.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/waypoints/waypoints.min.js"></script>
    <script src="lib/counterup/counterup.min.js"></script>
    <script src="lib/lightbox/js/lightbox.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    </body>

<.php>