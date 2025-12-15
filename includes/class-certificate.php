<?php
if (!defined('ABSPATH')) {
    exit;
}

class IC_LMS_Certificate {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
    }

    public function add_menu() {
        add_menu_page(
            'Certificate',
            'Certificate',
            'manage_options',
            'ic-lms-certificate',
            [$this, 'render_page'],
            'dashicons-awards',
            50
        );
    }

    public function render_page() {
        if (isset($_POST['ic_generate_certificate']) && check_admin_referer('ic_generate_certificate_action', 'ic_certificate_nonce')) {
            $this->render_certificate();
        } else {
            $this->render_form();
        }
    }

    private function render_form() {
        // Fetch published courses
        $courses = get_posts([
            'post_type' => 'course',
            'numberposts' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Define templates
        $templates = [
            'completion' => [
                'label' => 'Standard Completion',
                'text' => 'This is to certify that the student has successfully completed the course requirements and demonstrated proficiency in the subject matter.'
            ],
            'excellence' => [
                'label' => 'Certificate of Excellence',
                'text' => 'This award is presented in recognition of outstanding performance and dedication shown throughout the course.'
            ],
            'participation' => [
                'label' => 'Certificate of Participation',
                'text' => 'This acknowledges that the student has actively participated in the training sessions and completed all assigned modules.'
            ],
            'achievement' => [
                'label' => 'Certificate of Achievement',
                'text' => 'Presented for successfully acquiring new skills and knowledge through the completion of this comprehensive course.'
            ]
        ];

        // Define styles
        $styles = [
            'classic' => 'Classic',
            'modern' => 'Modern',
            'minimal' => 'Minimal',
        ];

        ?>
        <div class="wrap ic-lms-certificate-wrap">
            <h1 class="wp-heading-inline">Generate Certificate</h1>
            <p>Enter the details below to generate a completion certificate.</p>
            <hr class="wp-header-end">

            <div class="card">
                <form method="post" action="">
                    <?php wp_nonce_field('ic_generate_certificate_action', 'ic_certificate_nonce'); ?>
                    
                    <div class="form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" class="regular-text" required placeholder="e.g. John Doe">
                    </div>

                    <div class="form-group">
                        <label for="course_name">Course Name</label>
                        <select id="course_name" name="course_name" class="regular-text" required>
                            <option value="">Select a Course...</option>
                            <?php foreach ($courses as $course) : ?>
                                <option value="<?php echo esc_attr($course->post_title); ?>"><?php echo esc_html($course->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>



                    <div class="form-group">
                        <label for="certificate_style">Certificate Style</label>
                        <select id="certificate_style" name="certificate_style" class="regular-text">
                            <?php foreach ($styles as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="template_select">Message Template</label>
                        <select id="template_select" class="regular-text">
                            <option value="">Select a Template...</option>
                            <?php foreach ($templates as $key => $template) : ?>
                                <option value="<?php echo esc_attr($template['text']); ?>"><?php echo esc_html($template['label']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Select a template to auto-fill the text below.</p>
                    </div>

                    <div class="form-group">
                        <label for="template_text">Certificate Text</label>
                        <textarea id="template_text" name="template_text" class="large-text" rows="4" required>This is to certify that the student has successfully completed the course requirements.</textarea>
                    </div>

                    <p class="submit">
                        <input type="submit" name="ic_generate_certificate" id="submit" class="button button-primary button-large" value="Generate Certificate">
                    </p>
                </form>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const templateSelect = document.getElementById('template_select');
                const templateText = document.getElementById('template_text');

                templateSelect.addEventListener('change', function() {
                    if (this.value) {
                        templateText.value = this.value;
                    }
                });
            });
        </script>

        <style>
            .ic-lms-certificate-wrap {
                max-width: 800px;
                margin: 20px 0;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            }
            .ic-lms-certificate-wrap h1 {
                font-weight: 600;
                color: #1e1e1e;
            }
            .ic-lms-certificate-wrap .card {
                background: #fff;
                border: 1px solid #dcdcde;
                padding: 40px;
                margin-top: 20px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
                border-radius: 4px;
            }
            .ic-lms-certificate-wrap .form-group {
                margin-bottom: 25px;
            }
            .ic-lms-certificate-wrap label {
                display: block;
                font-weight: 500;
                margin-bottom: 8px;
                color: #3c434a;
                font-size: 14px;
            }
            .ic-lms-certificate-wrap .form-group input[type="text"],
            .ic-lms-certificate-wrap .form-group select,
            .ic-lms-certificate-wrap .form-group textarea {
                width: 100%;
                display: block;
                max-width: 100%;
                padding: 12px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                font-size: 14px;
                box-sizing: border-box;
                transition: border-color 0.15s ease-in-out;
            }
            .ic-lms-certificate-wrap input[type="text"]:focus,
            .ic-lms-certificate-wrap select:focus,
            .ic-lms-certificate-wrap textarea:focus {
                border-color: #2271b1;
                box-shadow: 0 0 0 1px #2271b1;
                outline: none;
            }
            .ic-lms-certificate-wrap .button-primary {
                background: #2271b1;
                border-color: #2271b1;
                text-shadow: none;
                box-shadow: none;
                padding: 6px 20px;
                font-size: 14px;
                height: auto;
                border-radius: 4px;
                transition: background 0.15s;
            }
            .ic-lms-certificate-wrap .button-primary:hover {
                background: #135e96;
                border-color: #135e96;
            }
            .ic-lms-certificate-wrap .description {
                margin-top: 6px;
                color: #646970;
                font-style: italic;
            }
        </style>
        <?php
    }

    private function render_certificate() {
        $student_name = sanitize_text_field($_POST['student_name']);
        $course_name = sanitize_text_field($_POST['course_name']);
        $template_text = sanitize_textarea_field($_POST['template_text']);
        $style = isset($_POST['certificate_style']) ? sanitize_text_field($_POST['certificate_style']) : 'classic';
        $date = date_i18n(get_option('date_format'));

        ?>
        <div class="ic-lms-print-preview">
            <div class="certificate-container style-<?php echo esc_attr($style); ?>">
                <div class="certificate-border">
                    <div class="certificate-content">
                        <div class="certificate-header">
                            <span class="certificate-icon"><dashicons class="dashicons dashicons-awards"></dashicons></span>
                            <h1>Certificate of Completion</h1>
                        </div>
                        
                        <div class="certificate-body">
                            <p class="cert-text"><?php echo esc_html($template_text); ?></p>
                            
                            <div class="cert-presented-to">
                                <span class="label">Presented to</span>
                                <h2 class="student-name"><?php echo esc_html($student_name); ?></h2>
                            </div>

                            <div class="cert-course">
                                <span class="label">For completing the course</span>
                                <h3 class="course-name"><?php echo esc_html($course_name); ?></h3>
                            </div>
                        </div>

                        <div class="certificate-footer">
                            <div class="cert-date">
                                <span class="date-line"><?php echo esc_html($date); ?></span>
                                <span class="label">Date</span>
                            </div>
                            <div class="cert-signature">
                                <span class="signature-line">Administrator</span>
                                <span class="label">Signature</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="no-print print-actions">
                <button onclick="window.print()" class="button button-primary button-hero">Print Certificate</button>
                <a href="<?php echo admin_url('admin.php?page=ic-lms-certificate'); ?>" class="button button-hero">Back to Generator</a>
            </div>
        </div>

        <style>
            @import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Pinyon+Script&family=Lato:wght@300;400;700&display=swap');

            .ic-lms-print-preview {
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 40px;
                background: #f0f0f1;
                min-height: 100vh;
            }

            .certificate-container {
                width: 1000px;
                height: 700px;
                background: #ffffff;
                padding: 20px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                color: #333;
                position: relative;
                box-sizing: border-box;
            }

            .certificate-border {
                border: 2px solid #2271b1;
                height: 100%;
                box-sizing: border-box;
                padding: 4px;
                position: relative;
            }
            
            .certificate-border:before {
                content: '';
                position: absolute;
                top: 4px; left: 4px; right: 4px; bottom: 4px;
                border: 1px solid #2271b1;
            }

            .certificate-content {
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                text-align: center;
                padding: 40px;
                box-sizing: border-box;
            }

            .certificate-header h1 {
                font-family: 'Cinzel', serif;
                font-size: 48px;
                text-transform: uppercase;
                letter-spacing: 4px;
                color: #2271b1;
                margin: 0;
                font-weight: 700;
            }

            .certificate-icon .dashicons {
                font-size: 60px;
                width: 60px;
                height: 60px;
                color: #2271b1;
                margin-bottom: 20px;
            }

            .certificate-body {
                margin: 40px 0;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                justify-content: center;
            }

            .cert-text {
                font-family: 'Lato', sans-serif;
                font-size: 18px;
                color: #666;
                margin-bottom: 30px;
            }

            .label {
                display: block;
                font-family: 'Lato', sans-serif;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 2px;
                color: #999;
                margin-bottom: 10px;
            }

            .student-name {
                font-family: 'Pinyon Script', cursive;
                font-size: 64px;
                margin: 10px 0 30px;
                color: #000;
                line-height: 1;
            }

            .course-name {
                font-family: 'Cinzel', serif;
                font-size: 28px;
                margin: 10px 0;
                color: #333;
                font-weight: 400;
            }

            .certificate-footer {
                width: 100%;
                display: flex;
                justify-content: space-between;
                padding: 0 60px;
                box-sizing: border-box;
                margin-top: auto;
            }

            .cert-date, .cert-signature {
                text-align: center;
                width: 200px;
            }

            .date-line, .signature-line {
                display: block;
                border-bottom: 1px solid #ccc;
                padding-bottom: 5px;
                margin-bottom: 10px;
                font-family: 'Lato', sans-serif;
                font-size: 18px;
                min-height: 27px;
            }

            .print-actions {
                margin-top: 30px;
                display: flex;
                justify-content: center;
                gap: 20px;
            }

            .print-actions .button {
                margin-right: 0;
                min-width: 200px;
                justify-content: center;
                text-align: center;
            }

            @media print {
                body * {
                    visibility: hidden;
                }
                .certificate-container, .certificate-container * {
                    visibility: visible;
                }
                .certificate-container {
                    position: absolute;
                    left: 0;
                    top: 0;
                    width: 100%;
                    height: 100%;
                    margin: 0;
                    padding: 0;
                    box-shadow: none;
                }
                .no-print {
                    display: none;
                }
                .ic-lms-print-preview {
                    padding: 0;
                    background: #fff;
                }
                @page {
                    size: landscape;
                    margin: 0;
                }
            }

            /* Modern Style */
            .certificate-container.style-modern {
                font-family: 'Roboto', sans-serif;
                background: #fdfdfd;
                color: #222;
            }
            .style-modern .certificate-border {
                border: 10px solid #2c3e50;
                padding: 0;
            }
            .style-modern .certificate-border:before {
                display: none;
            }
            .style-modern .certificate-header h1 {
                font-family: 'Roboto', sans-serif;
                color: #2c3e50;
                text-transform: uppercase;
                letter-spacing: 2px;
                font-weight: 900;
            }
            .style-modern .certificate-icon .dashicons {
                color: #2c3e50;
            }
            .style-modern .student-name {
                font-family: 'Roboto', sans-serif;
                font-weight: 300;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #2c3e50;
            }
            .style-modern .course-name {
                font-family: 'Roboto', sans-serif;
                font-weight: 700;
                color: #34495e;
            }

            /* Minimal Style */
            .certificate-container.style-minimal {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                background: #fff;
            }
            .style-minimal .certificate-border {
                border: none;
                padding: 40px;
            }
            .style-minimal .certificate-border:before {
                display: none;
            }
            .style-minimal .certificate-header h1 {
                font-family: 'Helvetica Neue', sans-serif;
                font-weight: 300;
                font-size: 36px;
                color: #333;
                text-transform: none;
                letter-spacing: 0;
            }
            .style-minimal .certificate-icon {
                display: none;
            }
            .style-minimal .student-name {
                font-family: 'Helvetica Neue', sans-serif;
                font-weight: 600;
                font-size: 48px;
                margin: 40px 0;
            }
            .style-minimal .course-name {
                font-family: 'Helvetica Neue', sans-serif;
                font-weight: 400;
                font-size: 24px;
                color: #666;
            }
            .style-minimal .cert-text {
                font-style: italic;
                color: #888;
            }
            .style-minimal .date-line,
            .style-minimal .signature-line {
                border-bottom: 1px solid #eee;
            }
        </style>
        <?php
    }
}
