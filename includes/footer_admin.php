        </div> <!-- /.container -->
        </div> <!-- /.main-content -->

        <footer class="footer bg-light text-center py-3 mt-5">
            &copy; <?= date('Y'); ?> Perpustakaan SMKN 8 PANDEGLANG Digital
        </footer>

        <script src="../js/bootstrap.bundle.min.js"></script>
        <script src="../js/sweetalert2.all.min.js"></script>
        <script src="../js/sidebar.js"></script>

        <?php
        // additional scripts if provided
        if (!empty($extraJs) && is_array($extraJs)) {
            foreach ($extraJs as $jsFile) {
                echo "    <script src=\"{$jsFile}\"></script>\n";
            }
        }
        ?>
        </body>

        </html>