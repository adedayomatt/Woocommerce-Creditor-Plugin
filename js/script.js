const site_url = '';

jQuery(document).ready(function(){
    
    jQuery('button#cred-lookup').click(function(e){
        e.preventDefault();
        let phone = jQuery(this).data('phone');
        const resultContainer = jQuery('#cred-lookup-result');
        resultContainer.css({'padding': '10px', 'border' : '1px solid #e3e3e3', 'borderRadius' : '5px'});
        resultContainer.html(`
            <div> Hold on, Looking up ${phone} on CredEquity...</div>
        `);
        
        if(!phone){
            resultContainer.html(`
                <div style="color: red">No valid phone number to look up with.</div>
            `);
            return;
        }
        jQuery.ajax({
            url: `${site_url}/ajax/index.php`,
            type: "POST",
            data: {
                url: `${params.cred_api}?PhoneNo=${phone}`,
                method: 'POST',
                headers: {
                    'Access-Key': params.cred_access_key,
                    'Content-Type': 'application/json'
                }
            }
        })
        .done(function(response, textStatus, jqXHR){
            const {data} = response;

            resultContainer.html(`
                <div><h4> Look up Result</h4></div>
            `);

            resultContainer.append(`
                <div>
                    <ul>
                        <li> Fullname: ${data.FullName}</li>
                        <li> Phone: ${data.Phonenumber}</li>
                        <li> Score: ${data.Score}</li>
                        <li> Gender: ${data.Gender}</li>
                        <li> BVN: ${data.Bvn}</li>
                        <li> Date of Birth: ${data.DateOFBirth}</li>
                        <li> Delinquency: ${data.Delinquency}</li>
                        <li> IsLoan: ${data.IsLoan}</li>
                        <li> Linked Email: ${data.LinkedEmail}</li>
                        <li> Loan Amount: ${data.LoanAmounts}</li>
                        <li> Last Updated: ${data.LastUpdatedDate}</li>
                    </ul>
                    <h4>Performance Summary</h4>
                </div>
            `);

            let performanceSummary = ``;
            let summaryList = (summary) => {
                return  `
                    <div>
                        <ul>
                            <li>ACCOUNT BALANCE: ${summary.ACCOUNT_BALANCE}</li>
                            <li>APPROVED AMOUNT: ${summary.APPROVED_AMOUNT}</li>
                            <li>DATA PRDR ID: ${summary.DATA_PRDR_ID}</li>
                            <li>DISHONORED CHEQUES_COUNT: ${summary.DISHONORED_CHEQUES_COUNT}</li>
                            <li>FACILITIES COUNT: ${summary.FACILITIES_COUNT}</li>
                            <li>INSTITUTION NAME: ${summary.INSTITUTION_NAME}</li>
                            <li>NONPERFORMING FACILITY: ${summary.NONPERFORMING_FACILITY}</li>
                            <li>OVERDUE AMOUNT: ${summary.OVERDUE_AMOUNT}</li>
                            <li>PERFORMING FACILITY: ${summary.PERFORMING_FACILITY}</li>
                        </ul>
                    </div
                    `
            }
            
            if(data.PerformanceSummary && data.PerformanceSummary.length){
                data.PerformanceSummary.forEach( summary => {
                    performanceSummary += `
                        ${summaryList(summary)}
                        <hr>
                    `;
                });
            }else{
                performanceSummary += `<div>Nothing to show</div>`;
            }
            resultContainer.append(performanceSummary);
        })
        .fail(function(jqXHR, textStatus, errorThrown){
            resultContainer.html(`
                <div> Failed: ${textStatus}</div>
            `);
        });
    })
});
