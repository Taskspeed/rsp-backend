<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            background-color: #e9ecef;
            margin: 0;
            padding: 30px 0;
            color: #333;
            font-size: 11pt;
        }

        .container {
            max-width: 800px;
            background: #ffffff;
            margin: 0 auto;
            border: 1px solid #dcdcdc;
            padding: 60px;
            box-shadow: 0px 0px 12px rgba(0, 0, 0, 0.07);
        }

        .letterhead {
            text-align: center;
            margin-bottom: 30px;
        }

        .letterhead img {
            max-width: 200px;
            display: block;
            margin: 0 auto 10px;
        }

        .letterhead-text {
            color: #00703c;
            line-height: 1.3;
        }

        .letterhead-text div:nth-child(1),
        .letterhead-text div:nth-child(2) {
            font-weight: 500;
        }

        .letterhead-text div:nth-child(3) {
            font-weight: bold;
            text-transform: uppercase;
        }

        .date {
            margin: 30px 0 20px 0;
        }

        .recipient {
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .recipient strong {
            display: block;
            margin-bottom: 5px;
        }

        .content p {
            line-height: 1.7;
            text-align: justify;
            margin: 15px 0;
        }

        .qualification-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .qualification-table th,
        .qualification-table td {
            border: 1px solid #333;
            padding: 10px;
            text-align: left;
            vertical-align: top;
        }

        .qualification-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }

        .signature-section {
            margin-top: 50px;
        }

        .signature-name {
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 50px;
        }

        .signature-title {
            font-style: italic;
        }

        .signature-auth {
        }

        .signatureImage {
            max-width: 300px;
            height: auto;
            display: block;
        }

        .header-image {
            width: 100%;
            height: 110px;
        }

        .footer-note {
            margin-top: 60px;
            padding-top: 12px;
            border-top: 1px solid #dcdcdc;
            text-align: center;
            color: #999;
            font-style: italic;
            letter-spacing: 0.3px;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Letterhead -->
        <div class="header">

            <img src="{{ $message->embed(public_path('images/emailHeader.png')) }}" alt="Logo" class="header-image">
        </div>

        <!-- Date -->
        <div class="date">
            {{ $date }}
        </div>
        <!-- Recipient -->
        <div class="recipient">
            <p>
                <strong>Dear {{ $fullname }}</strong>
                {{ ucfirst(strtolower($Rpurok)) }}
                {{ ucwords(strtolower($street)) }}
                {{ ucwords(strtolower($barangay)) }}<br>
                {{ ucwords(strtolower($city)) }}
                {{ ucwords(strtolower($province)) }}
            </p>
        </div>

        <div class="recipient">
            Dear {{ $fullname }},
        </div>
        <p>
            Greetings of Peace and Safety!
        </p>

        <!-- Content -->
        <div class="content">
            <p>
                This pertains to your application with the City Government of Tagum. We sincerely appreciate your interest in joining the City Government and the time and effort you have invested throughout the recruitment and selection process.
            </p>

            <p>
                After careful evaluation and consideration of all qualified applicants, we would like to inform you that you were not selected for appointment to the position/s you applied at this time. Please be assured that this decision does not diminish our appreciation of your qualifications, experience, and the effort you have demonstrated throughout the process.
            </p>
            <p>
                We encourage you to continue exploring future opportunities with the City Government of Tagum and to apply for positions that match your qualifications and experience. </p>
            <p>
                Thank you for your interest, and willingness to join the City Government of Tagum. We truly appreciate your participation in the selection process and wish you success in your future endeavors.
            </p>

        </div>

        <!-- Signature -->
        <div class="signature-section">
            <p><strong>(SGD.) EDGARD C. DE GUZMAN</strong><br>
                City Administrator<br>
                Authorized Representative of the City Mayor<br>
                Chairperson
            </p>


        </div>
        <!-- Footer -->

        <div class="footer-note">
            This document is system generated. Please do not reply to this message.
        </div>
    </div>

</body>

</html>