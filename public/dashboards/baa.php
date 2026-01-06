<?php
require_once '../../dashboards/_init.php'; // or wherever session is started
require_once '../../bootstrap.php';




if (empty($_SESSION['user_data']['user_uuid'])) {
    header('Location: /login.php');
    exit;
}
  //error_log($_SESSION['user_data']['user_uuid']);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_uuid = $_SESSION['user_data']['user_uuid'];
    
    try {
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        INSERT INTO baa_acceptance (
            user_uuid, covered_entity, business_type, state_province,
            representative_name, representative_title, signature_data
        ) VALUES (
            :user_uuid, :covered_entity, :business_type, :state_province,
            :representative_name, :representative_title, :signature_data
        )
    ");

    $success = $stmt->execute([
        ':user_uuid' => $user_uuid,
        ':covered_entity' => $_POST['covered_entity'],
        ':business_type' => $_POST['business_type'],
        ':state_province' => $_POST['state_province'],
        ':representative_name' => $_POST['representative_name'],
        ':representative_title' => $_POST['representative_title'],
        ':signature_data' => base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['signature_data']))
    ]);

    if ($success && $stmt->rowCount() > 0) {
    $_SESSION['baa_accepted'] = true;
    $_SESSION['toast_success'] = 'Business Associate Agreement accepted successfully!';
    header("Location: /dashboards/baa.php");
    exit;
    } else {
        error_log("BAA insert error: " . $e->getMessage());
        $_SESSION['toast_error'] = 'An error occurred while saving your BAA. Please try again.';
        header("Location: /dashboards/baa.php");
        exit;
    }
} catch (Exception $e) {
    error_log("BAA insert error: " . $e->getMessage());
    die('An error occurred while saving your BAA. Please try again later.');
}


    /*
    $stmt = $GLOBALS['pdo_hipaa']->prepare("
        INSERT INTO baa_acceptance (
            user_uuid, covered_entity, business_type, state_province,
            representative_name, representative_title, signature_data
        ) VALUES (
            :user_uuid, :covered_entity, :business_type, :state_province,
            :representative_name, :representative_title, :signature_data
        )
    ");

    $stmt->execute([
        ':user_uuid' => $user_uuid,
        ':covered_entity' => $_POST['covered_entity'],
        ':business_type' => $_POST['business_type'],
        ':state_province' => $_POST['state_province'],
        ':representative_name' => $_POST['representative_name'],
        ':representative_title' => $_POST['representative_title'],
        ':signature_data' => base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['signature_data']))
    ]);

    $_SESSION['baa_accepted'] = true;
    header("Location: /dashboards/baa.php");
    exit;
    */
}

  
?>
<html>
    <head>
    <link rel="shortcut icon" href="img/favicon.ico">
      <script src="js/pages/signature_pad.umd.min.js"></script>
      <script src="plugins/jquery/js/jquery.min.js"></script>
       <script src="plugins/jquery-mask-plugin/js/jquery.mask.min.js"></script>
        <link href="plugins/toastr/css/toastr.min.css" rel="stylesheet">
        <script src="plugins/toastr/js/toastr.min.js"></script>
      <style>
       body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h1,h2 { text-align: center; }
    p {margin-left:175px; margin-right: 175px; text-align: left;}
    table { width: 100%; border-collapse: collapse; }
    td, th { border: 1px solid #ccc; padding: 8px; vertical-align: top; }
    .indented {text-indent:2em; margin-top:1em; margin-bottom:0.5em;}
    .signatures {text-align:center;}
      </style>
    </head>
    <body>
            <form id="baaForm" method="post">
            <h1>Business Associate Agreement</h1>
            <p>This BUSINESS ASSOCIATE AGREEMENT (the 'BAA') is made and entered into as of <?php echo date('m/d/Y');?> between and by
            <input type='text' required id='covered_entity' name="covered_entity" placeholder='Name of business'></input>, a  <input type='text' required id='business_type' name="business_type" placeholder='Business type (LLC, SCorp, etc)'></input><br>
            organized under the laws of the <input type='text' required id='state_province' name="state_province" placeholder='State of Province'></input> ('Covered Entity') and MKAdvantage Inc. a Corporation organized under the laws of the State of Florida, United State of America.<br>
            ('Business Associate'), in accordance with the meaning given to those terms at 45 CFR &sect;164.501). In this BAA, Covered Entity and Business Associate are each a 'Party' and, collectively, are the 'Parties'.</p>
            
            <h2>BACKGROUND</h2>
            <p>I. Covered Entity is either a 'covered entity' or 'business associate' of a covered entity as each are defined under the Health Insurance Portability and Accountability Act of 1996, Public Law 104-191, as amended by the HITECH Act (as defined below) and the related regulations promulgated by HHS (as defined below) (collectively, 'HIPAA') and, as such, is required to comply with HIPAA's provisions regarding the confidentiality and privacy of Protected Health Information (as defined below);
<br><br>II. The Parties have entered into or will enter into one or more agreements under which Business Associate provides or will provide certain specified services to Covered Entity (collectively, the 'Agreement');
<br><br>III. In providing services pursuant to the Agreement, Business Associate will have access to Protected Health Information;
<br><br>IV. By providing the services pursuant to the Agreement, Business Associate will become a 'business associate' of the Covered Entity as such term is defined under HIPAA;
<br><br>V.Both Parties are committed to complying with all federal and state laws governing the confidentiality and privacy of health information, including, but not limited to, the Standards for Privacy of Individually Identifiable Health Information found at 45 CFR Part 160 and Part 164, Subparts A and E (collectively, the 'Privacy Rule'); and
<br><br>VI. Both Parties intend to protect the privacy and provide for the security of Protected Health Information disclosed to Business Associate pursuant to the terms of this Agreement, HIPAA and other applicable laws.
            </p> 
            <p>&nbsp;</p>
            <h2>AGREEMENT</h2>
            <p>
            NOW, THEREFORE, in consideration of the mutual covenants and conditions contained herein and the continued provision of PHI by Covered Entity to Business Associate under the Agreement in reliance on this BAA, the Parties agree as follows:
            <br><br>
            <p><span style="font-weight:bold;">1. Definitions.</span> For purposes of this BAA, the Parties give the following meaning to each of the terms in this Section 1 below. Any capitalized term used in this BAA, but not otherwise defined, has the meaning given to that term in the Privacy Rule or pertinent law.</p>
            <br><br>
            <p class="indented">A. "Affiliate" means a subsidiary or affiliate of Covered Entity that is, or has been, considered a covered entity, as defined by HIPAA.</p>
            
            <p class="indented">B. "Breach" means the acquisition, access, use, or disclosure of PHI in a manner not permitted under the Privacy Rule which compromises the security or privacy of the PHI, as defined in 45 CFR &sect;164.402.</p>
            <p class="indented">C. "Breach Notification Rule" means the portion of HIPAA set forth in Subpart D of 45 CFR Part 164.</p>
            <p class="indented">D. "Data Aggregation" means, with respect to PHI created or received by Business Associate in its capacity as the "business associate" under HIPAA of Covered Entity, the combining of such PHI by Business Associate with the PHI received by Business Associate in its capacity as a business associate of one or more other "covered entity" under HIPAA, to permit data analyses that relate to the Health Care Operations (defined below) of the respective covered entities. The meaning of "data aggregation" in this BAA shall be consistent with the meaning given to that term in the Privacy Rule.</p>
            <p class="indented">E. "Designated Record Set" has the meaning given to such term under the Privacy Rule, including 45 CFR &sect;164.501.B.</p>
            <p class="indented">F. "De-Identify" means to alter the PHI such that the resulting information meets the requirements described in 45 CFR &sect;164.514(a) and (b).</p>
            <p class="indented">G. "Electronic PHI" means any PHI maintained in or transmitted by electronic media as defined in 45 CFR &sect;160.103.</p>
            <p class="indented">H. "Health Care Operations" has the meaning given to that term in 45 CFR &sect;164.501.</p>
            <p class="indented">I. "HHS" means the U.S. Department of Health and Human Services.</p>
            <p class="indented">J. "HITECH Act" means the Health Information Technology for Economic and Clinical Health Act, enacted as part of the American Recovery and Reinvestment Act of 2009, Public Law 111-005.</p>
            <p class="indented">K. "Individual" has the same meaning given to that term i in 45 CFR &sect;164.501 and 160.130 and includes a person who qualifies as a personal representative in accordance with 45 CFR &sect;164.502(g).</p>
            <p class="indented">L. "Privacy Rule" means that portion of HIPAA set forth in 45 CFR Part 160 and Part 164, Subparts A and E.</p>
            <p class="indented">M. "Protected Health Information" or "PHI" has the meaning given to the term "protected health information" in 45 CFR &sect;164.501 and 160.103, limited to the information created or received by Business Associate from or on behalf of Covered Entity.</p>
            <p class="indented">N. "Security Incident" means the attempted or successful unauthorized access, use, disclosure, modification, or destruction of information or interference with system operations in an information system.</p>
            <p class="indented">O. "Security Rule" means the Security Standards for the Protection of Electronic Health Information provided in 45 CFR Part 160 & Part 164, Subparts A and C.</p>
            <p class="indented">P. "Unsecured Protected Health Information" or "Unsecured PHI" means any "protected health information" as defined in 45 CFR &sect;164.501 and 160.103 that is not rendered unusable, unreadable or indecipherable to unauthorized individuals through the use of a technology or methodology specified by the HHS Secretary in the guidance issued pursuant to the HITECH Act and codified at 42 USC &sect;17932(h).</p>
            <br><br>
            <p style="font-weight:bold;">2. Use and Disclosure of PHI</p>
            <p class="indented">A. Except as otherwise provided in this BAA, Business Associate may use or disclose PHI as reasonably necessary to provide the services described in the Agreement to Covered Entity, and to undertake other activities of Business Associate permitted or required of Business Associate by this BAA or as required by law.</p>
            <p class="indented">B. Except as otherwise limited by this BAA or federal or state law, Covered Entity authorizes Business Associate to use the PHI in its possession for the proper management and administration of Business Associate's business and to carry out its legal responsibilities. Business Associate may disclose PHI for its proper management and administration, provided that (i) the disclosures are required by law; or (ii) Business Associate obtains, in writing, prior to making any disclosure to a third party (a) reasonable assurances from this third party that the PHI will be held confidential as provided under this BAA and used or further disclosed only as required by law or for the purpose for which it was disclosed to this third party and (b) an agreement from this third party to notify Business Associate immediately of any breaches of the confidentiality of the PHI, to the extent it has knowledge of the breach.</p>
            <p class="indented">C. Business Associate will not use or disclose PHI in a manner other than as provided in this BAA, as permitted under the Privacy Rule, or as required by law. Business Associate will use or disclose PHI, to the extent practicable, as a limited data set or limited to the minimum necessary amount of PHI to carry out the intended purpose of the use or disclosure, in accordance with Section 13405(b) of the HITECH Act (codified at 42 USC &sect;17935(b)) and any of the act's implementing regulations adopted by HHS, for each use or disclosure of PHI.</p>
            <p class="indented">D. Upon request, Business Associate will make available to Covered Entity any of Covered Entity's PHI that Business Associate or any of its agents or subcontractors have in their possession.</p>
            <p class="indented">E. Business Associate may use PHI to report violations of law to appropriate Federal and State authorities, consistent with 45 CFR &sect;164.502(j)(1).</p>
            <br><br>
            <p><span style="font-weight:bold;">3. Safeguards Against Misuse of PHI.</span> Business Associate will use appropriate safeguards to prevent the use or disclosure of PHI other than as provided by the Agreement or this BAA and Business Associate agrees to implement administrative, physical, and technical safeguards that reasonably and appropriately protect the confidentiality, integrity and availability of the Electronic PHI that it creates, receives, maintains or transmits on behalf of Covered Entity. Business Associate agrees to take reasonable steps, including providing adequate training to its employees to ensure compliance with this BAA and to ensure that the actions or omissions of its employees or agents do not cause Business Associate to breach the terms of this BAA.</p>
            <br><br>
            <p><span style="font-weight:bold;">4.Reporting Disclosures of PHI and Security Incidents.</span> Business Associate will report to Covered Entity in writing any use or disclosure of PHI not provided for by this BAA of which it becomes aware and Business Associate agrees to report to Covered Entity any Security Incident affecting Electronic PHI of Covered Entity of which it becomes aware. Business Associate agrees to report any such event within five business days of becoming aware of the event.</p>
            <br><br>
            <p><span style="font-weight:bold;">5. Reporting Breaches of Unsecured PHI.</span> Business Associate will notify Covered Entity in writing promptly upon the discovery of any Breach of Unsecured PHI in accordance with the requirements set forth in 45 CFR &sect;164.410, but in no case later than 30 calendar days after discovery of a Breach. Business Associate will reimburse Covered Entity for any costs incurred by it in complying with the requirements of Subpart D of 45 CFR &sect;164 that are imposed on Covered Entity as a result of a Breach committed by Business Associate.</p>
            <br><br>
            <p><span style="font-weight:bold;">6. Mitigation of Disclosures of PHI.</span> Business Associate will take reasonable measures to mitigate, to the extent practicable, any harmful effect that is known to Business Associate of any use or disclosure of PHI by Business Associate or its agents or subcontractors in violation of the requirements of this BAA.</p>
            <br><br>
            <p><span style="font-weight:bold;">7. Agreements with Agents or Subcontractors.</span> Business Associate will ensure that any of its agents or subcontractors that have access to, or to which Business Associate provides, PHI agree in writing to the restrictions and conditions concerning uses and disclosures of PHI contained in this BAA and agree to implement reasonable and appropriate safeguards to protect any Electronic PHI that it creates, receives, maintains or transmits on behalf of Business Associate or, through the Business Associate, Covered Entity. Business Associate shall notify Covered Entity, or upstream Business Associate, of all subcontracts and agreements relating to the Agreement, where the subcontractor or agent receives PHI as described in section 1.M. of this BAA. Such notification shall occur within 30 (thirty) calendar days of the execution of the subcontract by placement of such notice on the Business Associate's primary website. Business Associate shall ensure that all subcontracts and agreements provide the same level of privacy and security as this BAA.</p>
            <br><br>
            <p><span style="font-weight:bold;">8. Audit Report.</span> Upon request, Business Associate will provide Covered Entity, or upstream Business Associate, with a copy of its most recent independent HIPAA compliance report (AT-C 315), HITRUST certification or other mutually agreed upon independent standards based third party audit report. Covered entity agrees not to re-disclose Business Associate's audit report.</p>
            <br><br>
            <p><span style="font-weight:bold;">9. Access to PHI by Individuals.</span></p>
            <br><br>
            <p class="indented">A. Upon request, Business Associate agrees to furnish Covered Entity with copies of the PHI maintained by Business Associate in a Designated Record Set in the time and manner designated by Covered Entity to enable Covered Entity to respond to an Individual's request for access to PHI under 45 CFR &sect;164.524.</p>
            <br><br>
            <p class="indented">B. In the event any Individual or personal representative requests access to the Individual's PHI directly from Business Associate, Business Associate within ten business days, will forward that request to Covered Entity. Any disclosure of, or decision not to disclose, the PHI requested by an Individual or a personal representative and compliance with the requirements applicable to an Individual's right to obtain access to PHI shall be the sole responsibility of Covered Entity.</p>
            <br><br>
            <p><span style="font-weight:bold;">10. Amendment of PHI</span></p>
            <br><br>
            <p class="indented">A. Upon request and instruction from Covered Entity, Business Associate will amend PHI or a record about an Individual in a Designated Record Set that is maintained by, or otherwise within the possession of, Business Associate as directed by Covered Entity in accordance with procedures established by 45 CFR &sect;164.526. Any request by Covered Entity to amend such information will be completed by Business Associate within 15 business days of Covered Entity's request.</p>
            <br><br>
            <p class="indented">B. In the event that any Individual requests that Business Associate amend such Individual's PHI or record in a Designated Record Set, Business Associate within ten business days will forward this request to Covered Entity. Any amendment of, or decision not to amend, the PHI or record as requested by an Individual and compliance with the requirements applicable to an Individual's right to request an amendment of PHI will be the sole responsibility of Covered Entity.</p>
            <br><br>
            <p><span style="font-weight:bold;">11. Accounting of Disclosures.</span></p>
            <br><br>
            <p class="indented">A. Business Associate will document any disclosures of PHI made by it to account for such disclosures as required by 45 CFR &sect;164.528(a). Business Associate also will make available information related to such disclosures as would be required for Covered Entity to respond to a request for an accounting of disclosures in accordance with 45 CFR &sect;164.528. At a minimum, Business Associate will furnish Covered Entity the following with respect to any covered disclosures by Business Associate: (i) the date of disclosure of PHI; (ii) the name of the entity or person who received PHI, and, if known, the address of such entity or person; (iii) a brief description of the PHI disclosed; and (iv) a brief statement of the purpose of the disclosure which includes the basis for such disclosure.</p>
            <br><br>
            <p class="indented">B. Business Associate will furnish to Covered Entity information collected in accordance with this Section 10, within ten business days after written request by Covered Entity, to permit Covered Entity to make an accounting of disclosures as required by 45 CFR &sect;164.528, or in the event that Covered Entity elects to provide an Individual with a list of its business associates, Business Associate will provide an accounting of its disclosures of PHI upon request of the Individual, if and to the extent that such accounting is required under the HITECH Act or under HHS regulations adopted in connection with the HITECH Act.</p>
            <br><br>
            <p class="indented">C. In the event an Individual delivers the initial request for an accounting directly to Business Associate, Business Associate will within ten business days forward such request to Covered Entity.</p>
            <br><br>
            <p><span style="font-weight:bold;">12. Availability of Books and Records.</span> Business Associate will make available its internal practices, books, agreements, records, and policies and procedures relating to the use and disclosure of PHI, upon request, to the Secretary of HHS for purposes of determining Covered Entity's and Business Associate's compliance with HIPAA, and this BAA.</p>
            <br><br>
            <p><span style="font-weight:bold;">13. Responsibilities of Covered Entity.</span> With regard to the use and/or disclosure of Protected Health Information by Business Associate, Covered Entity agrees to:</p>
            <br><br>
            <p class="indented">A. Notify Business Associate of any limitation(s) in its notice of privacy practices in accordance with 45 CFR &sect;164.520, to the extent that such limitation may affect Business Associate's use or disclosure of PHI.</p>
            <br><br>
            <p class="indented">B. Notify Business Associate of any changes in, or revocation of, permission by an Individual to use or disclose Protected Health Information, to the extent that such changes may affect Business Associate's use or disclosure of PHI.</p>
            <br><br>
            <p class="indented">C. Notify Business Associate of any restriction to the use or disclosure of PHI that Covered Entity has agreed to in accordance with 45 CFR &sect;164.522, to the extent that such restriction may affect Business Associate's use or disclosure of PHI.</p>
            <br><br>
            <p class="indented">D. Except for data aggregation or management and administrative activities of Business Associate, Covered Entity shall not request Business Associate to use or disclose PHI in any manner that would not be permissible under HIPAA if done by Covered Entity.</p>
            <br><br>
            <p><span style="font-weight:bold;">14. Data Ownership.</span> Business Associate's data stewardship does not confer data ownership rights on Business Associate with respect to any data shared with it under the Agreement, including any and all forms thereof.</p>
            <br><br>
            <p><span style="font-weight:bold;">15. Term and Termination.</span></p>
            <br><br>
            <p class="indented">A. This BAA will become effective on the date first written above, and will continue in effect until all obligations of the Parties have been met under the Agreement and under this BAA.</p>
            <br><br>
            <p class="indented">B. Covered Entity may terminate immediately this BAA, the Agreement, and any other related agreements if Covered Entity makes a determination that Business Associate has breached a material term of this BAA and Business Associate has failed to cure that material breach, to Covered Entity's reasonable satisfaction, within 30 days after written notice from Covered Entity. Covered Entity may report the problem to the Secretary of HHS if termination is not feasible.</p>
            <br><br>
            <p class="indented">C. If Business Associate determines that Covered Entity has breached a material term of this BAA, then Business Associate will provide Covered Entity with written notice of the existence of the breach and shall provide Covered Entity with 30 days to cure the breach. Covered Entity's failure to cure the breach within the 30-day period will be grounds for immediate termination of the Agreement and this BAA by Business Associate. Business Associate may report the breach to HHS.</p>
            <br><br>
            <p class="indented">D. Upon termination of the Agreement or this BAA for any reason, all PHI maintained by Business Associate will be returned to Covered Entity or destroyed by Business Associate. Business Associate will not retain any copies of such information. This provision will apply to PHI in the possession of Business Associate's agents and subcontractors. If return or destruction of the PHI is not feasible, in Business Associate's reasonable judgment, Business Associate will furnish Covered Entity with notification, in writing, of the conditions that make return or destruction infeasible. Upon mutual agreement of the Parties that return or destruction of the PHI is infeasible, Business Associate will extend the protections of this BAA to such information for as long as Business Associate retains such information and will limit further uses and disclosures to those purposes that make the return or destruction of the information not feasible. The Parties understand that this Section 14.D. will survive any termination of this BAA.</p>
            <br><br>
            <p><span style="font-weight:bold;">16. Effect of BAA.</span></p>
            <br><br>
            <p class="indented">A. This BAA is a part of and subject to the terms of the Agreement, except that to the extent any terms of this BAA conflict with any term of the Agreement, the terms of this BAA will govern.</p>
            <br><br>
            <p class="indented">B. Except as expressly stated in this BAA or as provided by law, this BAA will not create any rights in favor of any third party.</p>
            <br><br>
            <p><span style="font-weight:bold;">17. Regulatory References.</span> A reference in this BAA to a section in HIPAA means the section as in effect or as amended at the time.</p>
            <br><br>
            <p><span style="font-weight:bold;">18. Notices.</span>All notices, requests and demands or other communications to be given under this BAA to a Party will be made via either first class mail, registered or certified or express courier, or electronic mail to chris@mkadvantage.com</p>
            <br><br>
           
            </p>
            <div class="signatures">
            <h3>Signed</h3>
            <img src="../assets/ChristopherSchaft.png" width="130" height="60" />
            <br>President, MKAdvantage, Inc.
            <br><br>
            <hr>
            <br>
            <h3>Representative Name</h3>
            <input type="text" required id="representative_name" name="representative_name">
            
            <h3>Representative Title</h3>
            <input type="text" required id="representative_title" name="representative_title">
            
            <h3>Signature</h3>
            
            <canvas id="signatureCanvas" width="400" height="200" style="border:1px solid #ccc;"></canvas>
            <input type="hidden" name="signature_data" id="signatureData">
            <br>
            <button type="button" onclick="clearSignature()">Clear Signature</button>
            <br>
            <button type="submit" onclick="captureSignature()">Accept and Save</button>
            <button type="button" id="printBaaBtn" class="btn btn-secondary mt-3">Print/Download</button>
            </div>
            </form>
            
            <script>
                const canvas = document.getElementById('signatureCanvas');
                const signaturePad = new SignaturePad(canvas);

                function clearSignature() {
                    signaturePad.clear();
                }

                function captureSignature() {
                    if (!signaturePad.isEmpty()) {
                        document.getElementById('signatureData').value = signaturePad.toDataURL();
                    } else {
                        alert('You must sign the agreement.')
                    }
                }
                document.getElementById('printBaaBtn').addEventListener('click', function () {
                    window.print();
                });
            </script>
           <?php 
            if (!empty($_SESSION['toast_error'])){
?>
<script>
    $(document).ready(function () {
        toastr.options = {
              "positionClass": "toast-top-center",
              "timeOut": "5000"
            };
        toastr.error("<?= addslashes($_SESSION['toast_error']) ?>");
    });
</script>

<?php unset($_SESSION['toast_error']); 

} else if (!empty($_SESSION['toast_success'])) {
    
?>
<script>
    $(document).ready(function () {
        toastr.options = {
              "positionClass": "toast-top-center",
              "timeOut": "1000"
            };
        toastr.success("<?= addslashes($_SESSION['toast_success']) ?>");
    });
</script>
<?php
    unset($_SESSION['toast_success']);
} 
            
             if (!empty($_SESSION['baa_accepted'])): ?>
            <script>
            $(document).ready(function () {
                toastr.success(
                  'Your BAA has been successfully saved. <br><br><button onclick="location.href=\'/dashboards/index.php\'" class=\'btn btn-sm btn-light mt-2\'>Go to Dashboard</button>',
                  'Success',
                  { timeOut: 10000, extendedTimeOut: 5000 }
                );
            });
            </script>
            <?php unset($_SESSION['baa_accepted']); ?>
            <?php endif; ?>
    </body>
</html>


