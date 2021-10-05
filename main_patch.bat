set /p old_SHA=請輸入舊版本 SHA: 
set /p new_SHA=請輸入新版本 SHA: 
git diff --name-only %old_SHA% %new_SHA% > main_patch.txt
7z a -tzip main_patch.zip @main_patch.txt