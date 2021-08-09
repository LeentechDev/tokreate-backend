<table cellspacing="0" cellpadding="0" border="0" style="color:#333;background:#fff;padding:0;margin:0;width:100%;font:15px/1.25em 'Helvetica Neue',Arial,Helvetica"> 
    <tbody>
        <tr width="100%"> 
            <td valign="top" align="left" style="background:#dadada;font:15px/1.25em 'Helvetica Neue',Arial,Helvetica"> 
                <table style="border:none;padding:0 18px;margin:50px auto;width:500px"> 
                    <tbody> 
                        <tr width="100%" height="60"> 
                            <td valign="top" align="left" style="border-top-left-radius:4px;border-top-right-radius:4px;background:#27709b url(https://ci5.googleusercontent.com/proxy/EX6LlCnBPhQ65bTTC5U1NL6rTNHBCnZ9p-zGZG5JBvcmB5SubDn_4qMuoJ-shd76zpYkmhtdzDgcSArG=s0-d-e1-ft#https://trello.com/images/gradient.png) bottom left repeat-x;padding:10px 18px;text-align:center"> 
                                <img height="50" width="50" src="{{url('logo.png')}}"> 
                            </td> 
                        </tr>   
                        <tr width="100%"> 
                            <td valign="top" align="left" style="background:#fff;padding:18px">
                                <h1 style="font-size:20px;margin:16px 0;color:#333;text-align:center"> Wallet Access </h1>
                                <p style="font:15px/1.25em 'Helvetica Neue',Arial,Helvetica;color:#333;text-align:center"> Thank you for requesting a wallet setup. Your wallet is now ready, please check the credential.  </p>

                                <hr>
                                <div style="background:#f6f7f8;border-radius:3px">
                                <table>
                                    <tr>
                                        <td width="200"><strong>Wallet Address: </strong></td>
                                        <td> {{$email_content->wallet_address}}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Encrypted Data: </strong></td>
                                        <td> {{$email_content->encrypted_data}}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Encryption Key: </strong></td>
                                        <td> {{$email_content->encryption_key}}</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Initialization Vector: </strong></td>
                                        <td> {{$email_content->initialization_vector}}</td>
                                    </tr>
                                </table>
                                </div>
                                <hr>
                                <p style="font:14px/1.25em 'Helvetica Neue',Arial,Helvetica;color:#333"> 
                                    <strong>Note:</strong> 
                                    Keep your seedphrase safe, it's your responsibility.
                                </p>
                                <h4>Tips on storing it safely</h4>
                                <ul>
                                    <li>Save a backup multiple places.</li>
                                    <li>Never share the phrase with anyone</li>
                                    <li>Be careful of phishing site.</li>

                                </ul>
                            </td>
                        </tr>
                    </tbody> 
                </table> 
            </td>
        </tr>
    </tbody> 
</table>