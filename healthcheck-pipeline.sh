#!/bin/sh
attempt_counter=0
max_attempts=10

printf 'Checking...'

until $(curl --output /dev/null --silent --head --fail http://localhost); do
    if [ ${attempt_counter} -eq ${max_attempts} ]; then
        echo -e "\nMax attempts reached!"
        exit 1
    fi

    printf '.'
    attempt_counter=$(($attempt_counter+1))
    sleep 3
done

echo -e "\nHealth check successful."
exit 0