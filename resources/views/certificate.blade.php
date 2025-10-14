<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>修了証書</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            background: #FFFEF9;
        }
        
        .certificate {
            width: 210mm;
            height: 297mm;
            background: #FFFEF9;
            position: relative;
            margin: 0 auto;
        }
        
        .border-outer {
            position: absolute;
            top: 7mm;
            left: 7mm;
            width: 196mm;
            height: 283mm;
            border: 2px solid #8B7355;
        }
        
        .border-inner {
            position: absolute;
            top: 9mm;
            left: 9mm;
            width: 192mm;
            height: 279mm;
            border: 1px solid #8B7355;
        }
        
        .content {
            position: relative;
            padding: 30mm 25mm;
            z-index: 10;
        }
        
        .title {
            font-size: 56px;
            font-weight: bold;
            text-align: center;
            margin-top: 10mm;
            margin-bottom: 20mm;
            letter-spacing: 8px;
            color: #2C2C2C;
            font-family: 'HGP行書体', 'MS PGothic', serif;
        }
        
        .school-name {
            font-size: 16px;
            margin-bottom: 25mm;
            color: #4A4A4A;
            font-family: 'HGP行書体', 'MS PGothic', serif;
        }
        
        .recipient-section {
            text-align: center;
            margin-bottom: 25mm;
        }
        
        .recipient {
            display: inline-block;
            font-size: 42px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #1A1A1A;
            font-family: 'HGP行書体', 'MS PGothic', serif;
        }
        
        .recipient-dono {
            display: inline-block;
            font-size: 28px;
            margin-left: 10px;
            font-family: 'HGP行書体', 'MS PGothic', serif;
        }
        
        .exam-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5mm;
            padding-bottom: 5mm;
            border-bottom: 1px solid #CCCCCC;
        }
        
        .exam-name {
            font-size: 20px;
            color: #3A3A3A;
            font-family: 'HGS教科書体', 'MS PGothic', sans-serif;
        }
        
        .rank-name {
            font-size: 32px;
            font-weight: bold;
            color: #2C2C2C;
            font-family: 'Broadway', 'Arial Black', sans-serif;
        }
        
        .abilities {
            margin-top: 8mm;
            margin-bottom: 12mm;
        }
        
        .ability-row {
            display: flex;
            align-items: center;
            margin-bottom: 6mm;
        }
        
        .ability-label {
            font-size: 18px;
            color: #3A3A3A;
            width: 150px;
            font-family: 'HGS教科書体', 'MS PGothic', sans-serif;
        }
        
        .stars {
            font-size: 22px;
            color: #FFD700;
            letter-spacing: 5px;
        }
        
        .separator {
            border-top: 1px solid #CCCCCC;
            margin: 12mm 0;
        }
        
        .message {
            font-size: 16px;
            color: #3A3A3A;
            line-height: 1.8;
            margin-bottom: 15mm;
            font-family: 'HGS教科書体', 'MS PGothic', sans-serif;
        }
        
        .date {
            font-size: 16px;
            color: #3A3A3A;
            margin-bottom: 12mm;
            font-family: 'HGS教科書体', 'MS PGothic', sans-serif;
        }
        
        .footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 8mm;
        }
        
        .logo-container {
            max-width: 180px;
        }
        
        .logo-image {
            max-width: 180px;
            max-height: 50px;
            height: auto;
        }
        
        .school-logo-fallback {
            background: #4A90E2;
            padding: 8px 12px;
            border-radius: 3px;
            color: white;
            width: 180px;
        }
        
        .school-logo-line1 {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .school-logo-line2 {
            font-size: 13px;
            font-weight: bold;
        }
        
        .signature-area {
            text-align: center;
        }
        
        .vice-principal {
            font-size: 18px;
            color: #2C2C2C;
            margin-bottom: 8px;
            font-family: 'HGP行書体', 'MS PGothic', serif;
        }
        
        .stamp-image {
            width: 44px;
            height: 44px;
        }
        
        .seal-fallback {
            display: inline-block;
            width: 44px;
            height: 44px;
            border: 2px solid #DC143C;
            border-radius: 50%;
            line-height: 40px;
            text-align: center;
            font-size: 14px;
            color: #DC143C;
            font-weight: bold;
            font-family: 'HGP行書体', 'MS PGothic', serif;
        }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="border-outer"></div>
        <div class="border-inner"></div>
        
        <div class="content">
            <div class="title">修了証書</div>
            
            <div class="school-name">{{ $schoolName }}</div>
            
            <div class="recipient-section">
                <span class="recipient">{{ $userName }}</span>
                <span class="recipient-dono">殿</span>
            </div>
            
            <div class="exam-info">
                <span class="exam-name">プログラマー適性検査</span>
                <span class="rank-name">{{ $rankName }}</span>
            </div>
            
            <div class="abilities">
                <div class="ability-row">
                    <span class="ability-label">規則発見力:</span>
                    <span class="stars">{{ str_repeat('★', $part1Stars) }}</span>
                </div>
                
                <div class="ability-row">
                    <span class="ability-label">空間把握力:</span>
                    <span class="stars">{{ str_repeat('★', $part2Stars) }}</span>
                </div>
                
                <div class="ability-row">
                    <span class="ability-label">問題解決力:</span>
                    <span class="stars">{{ str_repeat('★', $part3Stars) }}</span>
                </div>
            </div>
            
            <div class="separator"></div>
            
            <div class="message">
                あなたは本校にて、上記の成績を修めたこ<br>
                とをここに証します。
            </div>
            
            <div class="date">{{ $formattedDate }}</div>
            
            <div class="footer">
                <div class="logo-container">
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" alt="YICロゴ" class="logo-image">
                    @else
                        <div class="school-logo-fallback">
                            <div class="school-logo-line1">学校法人 YIC学院</div>
                            <div class="school-logo-line2">YIC情報ビジネス専門学校</div>
                        </div>
                    @endif
                </div>
                
                <div class="signature-area">
                    <div class="vice-principal">副校長　河津　道正</div>
                    @if(!empty($stampData))
                        <img src="data:image/gif;base64,{{ $stampData }}" alt="印鑑" class="stamp-image">
                    @else
                        <div class="seal-fallback">印</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>