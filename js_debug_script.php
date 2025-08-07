<?php
echo "=== JAVASCRIPT DEBUGGING SCRIPT ===\n";
echo "Copy đoạn code sau vào Browser Console để debug:\n\n";

echo "// === TEST 1: CHECK JQUERY ===\n";
echo "console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'Not loaded');\n";
echo "console.log('Bootstrap loaded:', typeof $.fn.tab !== 'undefined');\n\n";

echo "// === TEST 2: CHECK TAB ELEMENTS ===\n";
echo "console.log('Tab nav items:', $('#documentTabs .nav-item').length);\n";
echo "console.log('Tab content panes:', $('#documentTabsContent .tab-pane').length);\n";
echo "console.log('Active tab:', $('#documentTabs .nav-link.active').attr('id'));\n\n";

echo "// === TEST 3: CHECK HIDDEN INPUTS ===\n";
echo "const councilInput = document.getElementById('council_members');\n";
echo "if (councilInput) {\n";
echo "    console.log('Council input found:', true);\n";
echo "    console.log('Council input value length:', councilInput.value.length);\n";
echo "    console.log('Has newlines:', councilInput.value.includes('\\n'));\n";
echo "    console.log('First 100 chars:', councilInput.value.substring(0, 100));\n";
echo "} else {\n";
echo "    console.log('❌ Council input NOT found');\n";
echo "}\n\n";

echo "// === TEST 4: MANUAL TAB SWITCH ===\n";
echo "console.log('Testing manual tab switch...');\n";
echo "try {\n";
echo "    $('#contract-tab').tab('show');\n";
echo "    setTimeout(() => {\n";
echo "        console.log('✅ Contract tab switch successful');\n";
echo "        $('#decision-tab').tab('show');\n";
echo "        setTimeout(() => {\n";
echo "            console.log('✅ Decision tab switch successful');\n";
echo "            $('#report-tab').tab('show');\n";
echo "            setTimeout(() => {\n";
echo "                console.log('✅ Report tab switch successful');\n";
echo "                $('#proposal-tab').tab('show');\n";
echo "                console.log('✅ All tabs working!');\n";
echo "            }, 500);\n";
echo "        }, 500);\n";
echo "    }, 500);\n";
echo "} catch (error) {\n";
echo "    console.error('❌ Tab switch error:', error);\n";
echo "}\n\n";

echo "// === TEST 5: CHECK CSS VISIBILITY ===\n";
echo "setTimeout(() => {\n";
echo "    const activePane = document.querySelector('#documentTabsContent .tab-pane.active');\n";
echo "    if (activePane) {\n";
echo "        const style = window.getComputedStyle(activePane);\n";
echo "        console.log('Active pane display:', style.display);\n";
echo "        console.log('Active pane visibility:', style.visibility);\n";
echo "        console.log('Active pane opacity:', style.opacity);\n";
echo "    }\n";
echo "}, 1000);\n\n";

echo "// === TEST 6: EVENT LISTENERS ===\n";
echo "console.log('Checking tab click events...');\n";
echo "$('#documentTabs .nav-link').each(function(i, tab) {\n";
echo "    console.log('Tab', i, ':', $(tab).attr('id'), 'data-toggle:', $(tab).attr('data-toggle'));\n";
echo "});\n\n";

echo "=== END DEBUGGING SCRIPT ===\n";
?>
