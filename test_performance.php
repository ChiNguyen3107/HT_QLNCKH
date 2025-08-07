<?php
session_start();
require_once 'include/connect.php';

// Test performance for evaluation system
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Performance Test - Evaluation System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2>Performance Test - Evaluation System</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>JavaScript Loading Test</h5>
                    </div>
                    <div class="card-body">
                        <button id="testBtn" class="btn btn-primary">Test Performance</button>
                        <div id="results" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Database Performance Test</h5>
                    </div>
                    <div class="card-body">
                        <button id="dbTestBtn" class="btn btn-success">Test Database</button>
                        <div id="dbResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Evaluation System Test</h5>
                    </div>
                    <div class="card-body">
                        <form id="evaluationForm">
                            <div class="form-group">
                                <label>QD_SO:</label>
                                <input type="text" class="form-control" id="qd_so" value="QDDT0">
                            </div>
                            <div class="form-group">
                                <label>GV_MAGV:</label>
                                <input type="text" class="form-control" id="gv_magv" value="GV001">
                            </div>
                            <div class="form-group">
                                <label>Điểm:</label>
                                <input type="number" class="form-control" id="score" value="8.5" step="0.1" min="0" max="10">
                            </div>
                            <button type="button" id="testEvaluation" class="btn btn-warning">Test Evaluation</button>
                        </form>
                        <div id="evalResults" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Optimized JavaScript Loading -->
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" defer></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js" defer></script>

    <script>
        // Performance monitoring
        let performanceData = {};
        
        window.addEventListener('load', function() {
            const loadTime = performance.now();
            console.log('Page load time:', loadTime + 'ms');
            
            // Initialize tests
            initializePerformanceTests();
        });

        function initializePerformanceTests() {
            $('#testBtn').click(function() {
                testJavaScriptPerformance();
            });

            $('#dbTestBtn').click(function() {
                testDatabasePerformance();
            });

            $('#testEvaluation').click(function() {
                testEvaluationSystem();
            });
        }

        function testJavaScriptPerformance() {
            const startTime = performance.now();
            
            // Simulate heavy JavaScript operations
            for (let i = 0; i < 100000; i++) {
                // Light computation
                Math.random() * Math.PI;
            }
            
            const endTime = performance.now();
            const executionTime = endTime - startTime;
            
            $('#results').html(`
                <div class="alert ${executionTime < 100 ? 'alert-success' : 'alert-warning'}">
                    <strong>JavaScript Performance:</strong><br>
                    Execution Time: ${executionTime.toFixed(2)}ms<br>
                    Status: ${executionTime < 100 ? 'Good' : 'Needs Optimization'}
                </div>
            `);
        }

        function testDatabasePerformance() {
            const startTime = performance.now();
            
            $.ajax({
                url: 'debug_member_evaluation.php',
                method: 'GET',
                dataType: 'html',
                success: function(response) {
                    const endTime = performance.now();
                    const executionTime = endTime - startTime;
                    
                    $('#dbResults').html(`
                        <div class="alert ${executionTime < 500 ? 'alert-success' : 'alert-warning'}">
                            <strong>Database Performance:</strong><br>
                            Response Time: ${executionTime.toFixed(2)}ms<br>
                            Status: ${executionTime < 500 ? 'Good' : 'Slow'}
                        </div>
                    `);
                },
                error: function() {
                    $('#dbResults').html(`
                        <div class="alert alert-danger">
                            <strong>Database Error:</strong> Could not connect to database
                        </div>
                    `);
                }
            });
        }

        function testEvaluationSystem() {
            const startTime = performance.now();
            const formData = {
                qd_so: $('#qd_so').val(),
                gv_magv: $('#gv_magv').val(),
                score: $('#score').val()
            };

            $.ajax({
                url: 'get_member_criteria_scores.php',
                method: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    const endTime = performance.now();
                    const executionTime = endTime - startTime;
                    
                    $('#evalResults').html(`
                        <div class="alert ${response.success ? 'alert-success' : 'alert-danger'}">
                            <strong>Evaluation System:</strong><br>
                            Response Time: ${executionTime.toFixed(2)}ms<br>
                            Status: ${response.success ? 'Working' : 'Error'}<br>
                            ${response.message || 'No message'}
                        </div>
                    `);
                },
                error: function(xhr, status, error) {
                    const endTime = performance.now();
                    const executionTime = endTime - startTime;
                    
                    $('#evalResults').html(`
                        <div class="alert alert-danger">
                            <strong>Evaluation System Error:</strong><br>
                            Response Time: ${executionTime.toFixed(2)}ms<br>
                            Error: ${error}<br>
                            Status: ${status}
                        </div>
                    `);
                }
            });
        }

        // Monitor console performance violations
        let originalConsoleWarn = console.warn;
        console.warn = function(...args) {
            if (args[0] && args[0].includes('violation')) {
                performanceData.violations = performanceData.violations || [];
                performanceData.violations.push(args[0]);
                
                // Display warning on page
                if ($('#performanceWarnings').length === 0) {
                    $('body').append('<div id="performanceWarnings" style="position:fixed;top:10px;right:10px;max-width:300px;z-index:9999;"></div>');
                }
                
                $('#performanceWarnings').append(`
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <small><strong>Performance Warning:</strong> ${args[0].substring(0, 100)}...</small>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                `);
            }
            originalConsoleWarn.apply(console, arguments);
        };
    </script>
</body>
</html>
