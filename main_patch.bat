set /p old_SHA=�п�J�ª��� SHA: 
set /p new_SHA=�п�J�s���� SHA: 
git diff --name-only %old_SHA% %new_SHA% > main_patch.txt
7z a -tzip main_patch.zip @main_patch.txt