export PATH="$PATH:$HOME/Dokumenty/Kod/PHP/map-tools" # skrypty do obróbki tekstur do Celestii

set_prompt () {
    Last_Command=$? # Must come first!
    Yellow='\[\e[01;33m\]'
    Blue='\[\e[01;34m\]'
    White='\[\e[01;37m\]'
    Red='\[\e[01;31m\]'
    Green='\[\e[01;32m\]'
    Reset='\[\e[00m\]'
    FancyX='\342\234\226'
    Checkmark='\342\234\224'

    PS1=""
    # Add a bright white exit status for the last command
    if [[ $Last_Command -lt 10 ]]; then
        PS1+=" "
    fi
    if [[ $Last_Command -lt 100 ]]; then
        PS1+=" "
    fi
    PS1+="$White\$Last_Command "
    # If it was successful, print a green check mark. Otherwise, print
    # a red X.
    if [[ $Last_Command == 0 ]]; then
        PS1+="$Green$Checkmark "
    else
        PS1+="$Red$FancyX "
    fi
    # If root, just print the host in red. Otherwise, print the current user
    # and host in green.
    if [[ $EUID == 0 ]]; then
        PS1+="$Red\\h "
    else
        PS1+="$Green\\u "
    fi
    # Print the working directory in blue and prompt marker in white, then 
    # print the command in yellow and reset for output
    PS1+="$Blue\\w $White\\$ $Yellow"
}
PROMPT_COMMAND='set_prompt'
trap 'printf "\e[0m" "$_"' DEBUG
